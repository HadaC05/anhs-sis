<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrarReportController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $filters = $request->validate([
            'report' => ['nullable', Rule::in(['enrollment', 'grades'])],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,SY_ID'],
            'grade_id' => ['nullable', 'integer', 'exists:grade_level,grade_ID'],
            'term_id' => ['nullable', 'integer', 'exists:grading_terms,term_ID'],
            'format' => ['nullable', Rule::in(['csv', 'print'])],
        ]);
        $report = $filters['report'] ?? 'enrollment';
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();
        $selectedYear = ! empty($filters['academic_year_id'])
            ? $academicYears->firstWhere('SY_ID', $filters['academic_year_id'])
            : ($academicYears->firstWhere('status', true) ?? $academicYears->first());
        $gradeId = $filters['grade_id'] ?? null;
        $termId = $report === 'grades' ? ($filters['term_id'] ?? null) : null;
        $gradeLevels = GradeLevel::query()->orderBy('grade_ID')->get();
        $terms = GradingTerm::query()->orderBy('sort_order')->get();
        $title = $report === 'grades' ? 'Grade Approval Status' : 'Enrollment Summary';
        $description = $report === 'grades'
            ? 'Counts of recorded learner grades by class and subject. No records means no grades have been encoded for the selected period; it does not measure missing required grades.'
            : 'Enrollment records by grade, section, and enrollment status, including learners without a section. A learner with multiple enrollment records is counted once per record.';

        if ($report === 'grades') {
            $headers = ['Grade level', 'Section', 'Subject', 'Teacher', ...array_column(GradeStatus::definitions(), 'name'), 'Total records'];
            $query = TeacherSubjectAssignment::query()->withoutMapehParents()
                ->where('SY_ID', $selectedYear?->SY_ID ?? 0)
                ->when($gradeId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('grade_ID', $gradeId)))
                ->with(['section.gradeLevel', 'curriculumSubject.subject', 'staff'])
                ->withCount(['grades' => fn ($q) => $q->when($termId, fn ($g) => $g->where('term_ID', $termId))]);
            foreach (GradeStatus::slugs() as $status) {
                $query->withCount(["grades as {$status}_count" => fn ($q) => $q->whereStatus($status)
                    ->when($termId, fn ($g) => $g->where('term_ID', $termId))]);
            }
            $rows = $query->orderBy('section_ID')->orderBy('assignment_ID')->get()->map(fn ($assignment) => [
                $assignment->section?->getRelation('gradeLevel')?->grade_label ?? 'Unspecified',
                $assignment->section?->name ?? 'Unassigned',
                $assignment->curriculumSubject?->subject?->title ?? 'Unspecified',
                trim(($assignment->staff?->first_name ?? '').' '.($assignment->staff?->last_name ?? '')) ?: 'Unassigned',
                ...array_map(fn ($status) => (int) $assignment->{"{$status}_count"}, GradeStatus::slugs()),
                (int) $assignment->grades_count,
            ]);
            $total = $rows->sum(fn ($row) => $row[count($row) - 1]);
            $totalLabel = 'Recorded grades';
        } else {
            $headers = ['Grade level', 'Section', 'Enrollment status', 'Enrollment records'];
            $rows = Enrollment::query()->where('SY_ID', $selectedYear?->SY_ID ?? 0)
                ->when($gradeId, fn ($q) => $q->forGrade((int) $gradeId))
                ->with(['gradeLevel', 'section', 'enrollmentStatus'])
                ->select(['curriculum_grade_level_ID', 'section_ID', 'enrollment_status_ID'])
                ->selectRaw('COUNT(*) as record_count')
                ->groupBy('curriculum_grade_level_ID', 'section_ID', 'enrollment_status_ID')
                ->get()->map(fn ($enrollment) => [
                    $enrollment->getRelation('gradeLevel')?->grade_label ?? 'Unspecified',
                    $enrollment->section?->name ?? 'Unassigned',
                    $enrollment->enrollmentStatus?->name ?? 'Unspecified',
                    (int) $enrollment->record_count,
                ])->groupBy(fn ($row) => json_encode(array_slice($row, 0, 3)))
                ->map(fn ($group) => [...array_slice($group->first(), 0, 3), $group->sum(fn ($row) => $row[3])])
                ->sortBy(fn ($row) => sprintf('%03d', (int) preg_replace('/\D/', '', $row[0])).$row[1].$row[2])->values();
            $total = $rows->sum(fn ($row) => $row[3]);
            $totalLabel = 'Enrollment records';
        }

        $scope = ($selectedYear?->school_year ?? 'No school year').' / '.($gradeLevels->firstWhere('grade_ID', $gradeId)?->grade_label ?? 'All grade levels');
        if ($report === 'grades') {
            $scope .= ' / '.($terms->firstWhere('term_ID', $termId)?->label ?? 'All grading terms');
        }
        $generatedAt = now()->timezone('Asia/Manila')->format('M d, Y h:i A');
        if (($filters['format'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($title, $scope, $generatedAt, $headers, $rows): void {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                foreach ([[$title], [$scope], ['Generated (Asia/Manila)', $generatedAt], $headers, ...$rows->all()] as $row) {
                    fputcsv($file, array_map(fn ($cell) => is_string($cell) && preg_match('/^[\s]*[=+@-]/u', $cell) ? "'".$cell : $cell, $row), ',', '"', '');
                }
                fclose($file);
            }, 'registrar-'.$report.'-'.($selectedYear?->SY_ID ?? 'none').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('users.registrar.reports', compact('report', 'academicYears', 'selectedYear', 'gradeLevels', 'gradeId', 'terms', 'termId', 'title', 'description', 'headers', 'rows', 'total', 'totalLabel', 'scope', 'generatedAt'));
    }
}
