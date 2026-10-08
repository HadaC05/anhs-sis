<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\SchoolInformation;
use App\Models\Section;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Sf1Export
{
    public static function download(Section $section): BinaryFileResponse
    {
        $section->loadMissing(['academicYear', 'gradeLevel', 'adviser', 'cluster']);
        $enrollments = Enrollment::query()
            ->with(['student.addresses', 'student.guardians', 'cluster', 'track', 'electives'])
            ->where('section_ID', $section->section_ID)
            ->where('SY_ID', $section->SY_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->get();
        $start = $section->academicYear?->start_date;
        $rows = $enrollments->filter(fn ($enrollment) => $enrollment->student)->map(function ($enrollment) use ($start): array {
            $student = $enrollment->student;
            $address = $student->addresses->firstWhere('address_type', 'current') ?? $student->addresses->firstWhere('address_type', 'permanent');
            $father = $student->guardians->firstWhere('relationship', 'father');
            $mother = $student->guardians->firstWhere('relationship', 'mother');
            $guardian = $student->guardians->first(fn ($person) => ! in_array(strtolower($person->relationship), ['father', 'mother'], true));
            $contact = collect([$guardian, $father, $mother])->filter(fn ($person) => $person && ! $person->is_deceased && filled($person->contact_no))->first()?->contact_no ?? '';
            $sex = match (strtolower((string) $student->sex)) {
                'male', 'm' => 'male',
                'female', 'f' => 'female',
                default => 'unspecified',
            };

            return [
                'sex' => $sex,
                'name' => self::name($student),
                'cells' => [
                    'A' => (string) $student->lrn,
                    'C' => self::name($student),
                    'G' => ['male' => 'M', 'female' => 'F'][$sex] ?? '',
                    'H' => $student->birthdate?->format('m/d/Y') ?? '',
                    'J' => $start && $student->birthdate && $student->birthdate->lte($start) ? (int) $student->birthdate->diffInYears($start) : '',
                    'L' => $student->religion ?? '',
                    'M' => trim(($address?->house_no ?? '').' '.($address?->street_name ?? '')),
                    'N' => $address?->barangay ?? '',
                    'R' => $address?->municipality ?? '',
                    'U' => $address?->province ?? '',
                    'W' => self::name($father),
                    'X' => self::name($mother),
                    'Z' => self::name($guardian),
                    'AC' => $guardian ? Str::title($guardian->relationship) : '',
                    'AD' => (string) $contact,
                    'AE' => '',
                ],
            ];
        })->sortBy(fn ($row) => mb_strtolower($row['name']))->values();
        $school = SchoolInformation::current();
        $gradeLabel = $section->getRelation('gradeLevel')?->grade_label ?? '';
        $grade = preg_replace('/\D/', '', $gradeLabel);
        $senior = in_array((int) $grade, [11, 12], true);
        $strands = $enrollments->map(fn ($enrollment) => Sf9ReportCardBuilder::trackStrand($enrollment, $section))->filter()->unique()->implode('; ');
        $path = Sf1Workbook::create($rows, [
            'A1' => $senior ? 'School Form 1 School Register for Senior High School (SF1-SHS)' : 'School Form 1 (SF1) School Register',
            'F3' => $school->name ?? '', 'M3' => (string) ($school->school_id ?? ''),
            'U3' => $school->district ?? '', 'Z3' => $school->division ?? '', 'AF3' => $school->region ?? '',
            'M5' => $section->academicYear?->school_year ?? '', 'W5' => $grade,
            'AC5' => $senior ? $strands : '', 'F7' => $section->name,
            'Y96' => trim(($section->adviser?->first_name ?? '').' '.($section->adviser?->last_name ?? '')),
            'U95' => 'Current class list',
            'A100' => 'Complete active class roster. Age as of '.($start?->format('m/d/Y') ?? 'school-year start').'. Missing information is left blank.',
        ]);

        return response()->download($path, 'SF1-'.Str::slug($section->name.'-'.$section->academicYear?->school_year).'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    private static function name($person): string
    {
        if (! $person) {
            return '';
        }

        return trim(($person->last_name ? $person->last_name.', ' : '').collect([$person->first_name, $person->suffix, $person->middle_name])->filter()->implode(' '));
    }
}
