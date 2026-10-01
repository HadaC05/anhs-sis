<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\EnrollmentStatus;
use App\Models\Section;
use App\Models\SectionAttendanceSetting;
use App\Models\SectionSf2Upload;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Sf2AttendanceImporter
{
    public function __construct(private Sf2AttendancePdf $reader) {}

    public function import(SectionSf2Upload $upload): void
    {
        try {
            $report = $this->reader->read(Storage::disk('local')->path($upload->storage_path));
            $upload->update([
                'report_year' => $report['year'],
                'source_school_year' => $report['school_year'],
                'school_days' => $report['school_days'],
                'class_dates' => $report['class_dates'],
                'import_rows' => $report['rows'],
                'sf2_layout' => $report['layout'] ?? null,
            ]);

            DB::transaction(function () use ($upload, $report): void {
                $section = Section::query()->with(['academicYear', 'gradeLevel'])->lockForUpdate()->findOrFail($upload->section_ID);
                $this->validateReport($section, $upload, $report);

                $enrollments = Enrollment::query()->with('student')
                    ->where('section_ID', $section->section_ID)
                    ->where('SY_ID', $section->SY_ID)
                    ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
                    ->get();
                $rows = $report['rows'];
                $matchedIds = [];
                foreach ($rows as &$row) {
                    $key = self::nameKey($row['name']);
                    $matches = $enrollments->filter(fn ($enrollment) => $enrollment->student && in_array($key, self::studentNameKeys($enrollment->student), true));
                    $row['enrollment_ID'] = $matches->count() === 1 ? $matches->first()->enrollment_ID : null;
                    $row['match_status'] = match ($matches->count()) {
                        0 => 'No matching enrolled learner',
                        1 => 'Matched',
                        default => 'Ambiguous name; multiple learners match',
                    };
                    if ($row['enrollment_ID']) {
                        if (in_array($row['enrollment_ID'], $matchedIds, true)) {
                            throw new Sf2ImportException('The PDF contains duplicate attendance rows for '.$row['name'].'. No attendance was changed.');
                        }
                        $matchedIds[] = $row['enrollment_ID'];
                    }
                }
                unset($row);
                $upload->update(['import_rows' => $rows]);

                if ($matchedIds === []) {
                    // Commit the matching diagnostics without changing any attendance.
                    $upload->update(['status' => 'failed', 'parse_notes' => 'No PDF names matched enrolled learners in this section and school year. Check the class list and school year, then upload again. No attendance was changed.']);

                    return;
                }

                // Do not change the section calendar underneath untouched records with a different total.
                $conflictingRecords = EnrollmentMonthlyAttendance::query()
                    ->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID'))
                    ->whereNotIn('enrollment_ID', $matchedIds)
                    ->where('month', $upload->report_month)
                    ->whereRaw('days_present + days_absent <> ?', [$report['school_days']])
                    ->exists();
                if ($conflictingRecords) {
                    throw new Sf2ImportException('This report changes the class-day total but omits learners with existing attendance. Upload a complete report for the section so the SF9 totals stay consistent.');
                }

                foreach ($rows as $row) {
                    if (! $row['enrollment_ID']) {
                        continue;
                    }
                    EnrollmentMonthlyAttendance::query()->updateOrCreate(
                        ['enrollment_ID' => $row['enrollment_ID'], 'month' => $upload->report_month],
                        [
                            'days_present' => $row['days_present'],
                            'days_absent' => $row['days_absent'],
                            'days_tardy' => $row['days_tardy'],
                            'source_sf2_upload_id' => $upload->id,
                        ]
                    );
                }
                SectionAttendanceSetting::query()->updateOrCreate(
                    ['section_ID' => $section->section_ID, 'SY_ID' => $section->SY_ID, 'month' => $upload->report_month],
                    ['school_days' => $report['school_days'], 'source_sf2_upload_id' => $upload->id]
                );
                $unmatched = count($rows) - count($matchedIds);
                $missing = $enrollments->count() - count($matchedIds);
                $upload->update([
                    'status' => $unmatched > 0 || $missing > 0 ? 'partial' : 'imported',
                    'imported_at' => now(),
                    'parse_notes' => count($matchedIds).' learner records imported. '.$unmatched.' PDF names could not be matched; '.$missing.' enrolled learners were not included. Unmatched or omitted learners were not changed. Present = '.$report['school_days'].' class days minus absences. Tardy is recorded separately.',
                ]);
            });
        } catch (Sf2ImportException $exception) {
            $upload->update(['status' => 'failed', 'parse_notes' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            report($exception);
            $upload->update(['status' => 'failed', 'parse_notes' => 'Attendance could not be imported. No attendance was changed. Please try uploading again.']);
        }
    }

    private function validateReport(Section $section, SectionSf2Upload $upload, array $report): void
    {
        $academicYear = $section->academicYear;
        if ($report['month'] !== $upload->report_month) {
            throw new Sf2ImportException('The PDF report month does not match the selected upload month. No attendance was changed.');
        }
        if ($report['school_year'] !== $academicYear->school_year) {
            throw new Sf2ImportException('The PDF is for school year '.$report['school_year'].' but this section is in '.$academicYear->school_year.'. Upload a PDF with the matching school year. No attendance was changed.');
        }
        $reportDate = sprintf('%04d-%02d', $report['year'], $report['month']);
        if ($reportDate < $academicYear->start_date->format('Y-m') || $reportDate > $academicYear->end_date->format('Y-m')) {
            throw new Sf2ImportException('The report date falls outside this section\'s school year. No attendance was changed.');
        }
        preg_match('/\d+/', $section->getRelation('gradeLevel')?->grade_label ?? '', $grade);
        if ($report['grade'] !== (int) ($grade[0] ?? 0) || self::sectionKey($report['section'], $report['grade']) !== self::sectionKey($section->name, $report['grade'])) {
            throw new Sf2ImportException('The PDF grade/section ('.$report['grade'].' / '.$report['section'].') does not match '.$section->name.'. No attendance was changed.');
        }
    }

    private static function sectionKey(string $name, int $grade): string
    {
        $key = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($name)));
        $key = preg_replace('/^(?:grade|g)(?=\d)/', '', $key);

        return preg_match('/^\d/', $key) ? $key : $grade.$key;
    }

    public static function nameKey(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($name)));
    }

    public static function studentNameKeys(Student $student): array
    {
        $middle = trim((string) $student->middle_name);
        $initials = implode(' ', array_map(fn ($part) => mb_substr($part, 0, 1), preg_split('/\s+/', $middle)));
        $keys = [];
        foreach (array_unique([$middle, $initials]) as $middleName) {
            $keys[] = self::nameKey($student->last_name.', '.$student->first_name.' '.$middleName.' '.$student->suffix);
            $keys[] = self::nameKey($student->last_name.' '.$student->suffix.', '.$student->first_name.' '.$middleName);
        }

        return array_unique($keys);
    }
}
