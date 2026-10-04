<?php

namespace App\Http\Controllers\Guidance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\PromotionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PromotionReportController extends Controller
{
    public function index(Request $request)
    {
        $statuses = collect(PromotionStatus::definitions())->pluck('name', 'slug')->all();
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,SY_ID'],
            'grade_id' => ['nullable', 'integer', 'exists:grade_level,grade_ID'],
            'status' => ['nullable', Rule::in(array_keys($statuses))],
            'enrollment_status' => ['nullable', Rule::in(EnrollmentStatus::slugs())],
            'search' => ['nullable', 'string', 'max:100'],
            'download' => ['nullable', Rule::in(['records', 'summary', 'chart'])],
        ]);
        $years = AcademicYear::query()->orderByDesc('start_date')->get();
        $grades = GradeLevel::query()->orderBy('grade_ID')->get();
        $filters['academic_year_id'] = $request->has('academic_year_id')
            ? ($filters['academic_year_id'] ?? '') : ($years->firstWhere('status', true)?->SY_ID ?? '');
        $query = DB::table('enrollments as e')
            ->leftJoin('students as s', 's.id', '=', 'e.student_ID')
            ->leftJoin('academic_years as y', 'y.SY_ID', '=', 'e.SY_ID')
            ->leftJoin('curriculum_grade_levels as c', 'c.curriculum_ID', '=', 'e.curriculum_grade_level_ID')
            ->leftJoin('grade_level as g', 'g.grade_ID', '=', 'c.grade_ID')
            ->leftJoin('sections as sec', 'sec.section_ID', '=', 'e.section_ID')
            ->leftJoin('promotion_statuses as ps', 'ps.promotion_status_ID', '=', 'e.promotion_status_ID')
            ->leftJoin('enrollment_statuses as es', 'es.enrollment_status_ID', '=', 'e.enrollment_status_ID');
        foreach (['academic_year_id' => 'e.SY_ID', 'grade_id' => 'c.grade_ID', 'status' => 'ps.slug', 'enrollment_status' => 'es.slug'] as $key => $column) {
            if (filled($filters[$key] ?? null)) {
                $query->where($column, $filters[$key]);
            }
        }
        foreach (preg_split('/[\s,]+/', trim($filters['search'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(function ($q) use ($term) {
                foreach (['s.lrn', 's.first_name', 's.middle_name', 's.last_name'] as $column) {
                    $q->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }
        $details = (clone $query)->select('e.enrollment_ID', 's.lrn', 's.last_name', 's.first_name', 's.middle_name', 's.suffix', 'y.school_year', 'g.grade_label', 'sec.name as section_name', 'es.name as enrollment_status', 'ps.name as promotion_status')
            ->orderBy('s.last_name')->orderBy('s.first_name')->orderBy('e.enrollment_ID');
        if (($filters['download'] ?? '') === 'records') {
            return response()->streamDownload(function () use ($details) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                $this->writeCsv($stream, ['Enrollment ID', 'LRN', 'Last name', 'First name', 'Middle name', 'Suffix', 'School year', 'Grade', 'Section', 'Enrollment status', 'Promotion status']);
                foreach ($details->lazy(500) as $row) {
                    $this->writeCsv($stream, array_values((array) $row));
                }
                fclose($stream);
            }, 'promotion-records-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        $total = (clone $query)->count();
        $counts = (clone $query)->select('ps.slug')->selectRaw('COUNT(*) as total')->groupBy('ps.slug')->pluck('total', 'slug');
        $rows = collect($statuses)->map(fn ($name, $slug) => (object) ['label' => $name, 'total' => (int) ($counts[$slug] ?? 0)])->values();
        if (isset($counts[''])) {
            $statuses[''] = 'Unspecified';
            $rows->push((object) ['label' => 'Unspecified', 'total' => (int) $counts['']]);
        }
        $matrices = [];
        foreach (['Grade level' => 'g.grade_label', 'School year' => 'y.school_year', 'Section' => 'sec.name'] as $title => $column) {
            $grouped = (clone $query)->select($column.' as label', 'ps.slug')->selectRaw('COUNT(*) as total')
                ->groupBy($column, 'ps.slug')->get()->groupBy(fn ($row) => $row->label ?: 'Unassigned / unspecified');
            $matrices[$title] = $grouped->map(fn ($group, $label) => [
                'label' => $label, 'counts' => $group->pluck('total', 'slug')->all(), 'total' => (int) $group->sum('total'),
            ])->sortBy('label', SORT_NATURAL)->values();
        }
        $reportContext = [
            'School year: '.($years->firstWhere('SY_ID', $filters['academic_year_id'])?->school_year ?? 'All school years'),
            'Grade: '.($grades->firstWhere('grade_ID', $filters['grade_id'] ?? null)?->grade_label ?? 'All'),
            'Promotion: '.($statuses[$filters['status'] ?? ''] ?? 'All'),
            'Enrollment: '.(EnrollmentStatus::options()[$filters['enrollment_status'] ?? ''] ?? 'All'),
            'Search: '.($filters['search'] ?? 'None'),
            'Stored statuses; eligible is not yet promoted. Grade 12 eligible means completed.',
        ];
        $generatedAt = now()->format('M d, Y, h:i A');
        if (($filters['download'] ?? '') === 'chart') {
            return response(view('users.guidance.reports.partials.enrollment-chart', [
                'title' => 'Promotion outcomes', 'reportName' => 'Promotion report',
                'rows' => $rows, 'total' => $total, 'reportContext' => $reportContext, 'generatedAt' => $generatedAt,
            ])->render(), 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="promotion-outcomes-'.now()->format('Y-m-d').'.svg"',
            ]);
        }
        if (($filters['download'] ?? '') === 'summary') {
            return response()->streamDownload(function () use ($matrices, $statuses, $rows, $reportContext, $generatedAt, $total) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                $this->writeCsv($stream, ['Promotion report', 'Generated '.$generatedAt]);
                foreach ($reportContext as $line) {
                    $this->writeCsv($stream, [$line]);
                }
                $this->writeCsv($stream, ['Promotion status', 'Records', 'Share (%)']);
                foreach ($rows as $row) {
                    $this->writeCsv($stream, [$row->label, $row->total, $total ? round($row->total / $total * 100, 1) : 0]);
                }
                foreach ($matrices as $title => $matrix) {
                    $this->writeCsv($stream, [$title, ...array_values($statuses), 'Total']);
                    foreach ($matrix as $row) {
                        $this->writeCsv($stream, [$row['label'], ...array_map(fn ($slug) => $row['counts'][$slug] ?? 0, array_keys($statuses)), $row['total']]);
                    }
                }
                fclose($stream);
            }, 'promotion-summary-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('users.guidance.reports.promotion', [
            'filters' => $filters, 'years' => $years, 'grades' => $grades, 'statuses' => $statuses,
            'enrollmentStatuses' => EnrollmentStatus::options(), 'total' => $total, 'rows' => $rows,
            'matrices' => $matrices, 'reportContext' => $reportContext, 'generatedAt' => $generatedAt,
            'records' => $details->paginate(25)->withQueryString(),
        ]);
    }

    private function writeCsv($stream, array $cells): void
    {
        fputcsv($stream, array_map(function ($value) {
            $value = (string) ($value ?? '');

            return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
        }, $cells));
    }
}
