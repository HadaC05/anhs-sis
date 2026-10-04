<?php

namespace App\Http\Controllers\Guidance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Support\EnrollmentYearComparison;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EnrollmentReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,SY_ID'],
            'grade_id' => ['nullable', 'integer', 'exists:grade_level,grade_ID'],
            'status' => ['nullable', Rule::in(EnrollmentStatus::slugs())],
            'learner_type' => ['nullable', Rule::in(LearnerType::slugs())],
            'section' => ['nullable', Rule::in(['assigned', 'unassigned'])],
            'search' => ['nullable', 'string', 'max:100'],
            'download' => ['nullable', Rule::in(['csv', 'summary', 'chart', 'comparison'])],
            'chart' => ['required_if:download,chart', 'nullable', Rule::in(['status', 'grade', 'learner', 'sex', 'year-trend', 'year-grade'])],
        ]);
        $years = AcademicYear::query()->orderByDesc('start_date')->get();
        if (! $request->has('academic_year_id')) {
            $filters['academic_year_id'] = $years->firstWhere('status', true)?->SY_ID;
        }
        $filters['academic_year_id'] = $filters['academic_year_id'] ?? '';

        $query = DB::table('enrollments as e')
            ->leftJoin('students as s', 's.id', '=', 'e.student_ID')
            ->leftJoin('academic_years as y', 'y.SY_ID', '=', 'e.SY_ID')
            ->leftJoin('curriculum_grade_levels as c', 'c.curriculum_ID', '=', 'e.curriculum_grade_level_ID')
            ->leftJoin('grade_level as g', 'g.grade_ID', '=', 'c.grade_ID')
            ->leftJoin('sections as sec', 'sec.section_ID', '=', 'e.section_ID')
            ->leftJoin('clusters as cl', 'cl.cluster_ID', '=', 'c.cluster_ID')
            ->leftJoin('enrollment_statuses as es', 'es.enrollment_status_ID', '=', 'e.enrollment_status_ID')
            ->leftJoin('learner_types as lt', 'lt.learner_type_ID', '=', 'e.learner_type_ID');

        foreach (['grade_id' => 'c.grade_ID', 'status' => 'es.slug', 'learner_type' => 'lt.slug'] as $key => $column) {
            if (filled($filters[$key] ?? null)) {
                $query->where($column, $filters[$key]);
            }
        }
        if (filled($filters['section'] ?? null)) {
            $filters['section'] === 'assigned' ? $query->whereNotNull('e.section_ID') : $query->whereNull('e.section_ID');
        }
        if (filled($filters['search'] ?? null)) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                foreach (['s.lrn', 's.first_name', 's.last_name', 's.middle_name'] as $column) {
                    $q->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $comparisonQuery = clone $query;
        if (filled($filters['academic_year_id'])) {
            $query->where('e.SY_ID', $filters['academic_year_id']);
        }

        $details = (clone $query)->select([
            'e.enrollment_ID', 's.lrn', 's.first_name', 's.middle_name', 's.last_name', 's.suffix', 's.sex',
            'y.school_year', 'g.grade_label', 'sec.name as section_name', 'cl.name as cluster_name',
            'es.name as status_name', 'lt.name as learner_type_name', 'e.created_at',
        ])->orderBy('s.last_name')->orderBy('s.first_name')->orderBy('e.enrollment_ID');

        if (($filters['download'] ?? null) === 'csv') {
            return response()->streamDownload(function () use ($details) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['Enrollment ID', 'LRN', 'Last name', 'First name', 'Middle name', 'Suffix', 'Sex', 'School year', 'Grade', 'Section', 'Cluster', 'Status', 'Learner type', 'Registered at']);
                foreach ($details->lazy(500) as $row) {
                    $cells = array_map(function ($value) {
                        $value = (string) ($value ?? '');

                        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
                    }, array_values((array) $row));
                    fputcsv($stream, $cells);
                }
                fclose($stream);
            }, 'enrollment-report-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $total = (clone $query)->count();
        $summary = [
            'Enrollment records' => $total,
            'Unique learners' => (clone $query)->distinct()->count('e.student_ID'),
            'Officially enrolled' => (clone $query)->where('es.slug', EnrollmentStatus::ENROLLED)->count(),
            'Temporarily enrolled' => (clone $query)->where('es.slug', EnrollmentStatus::TEMPORARILY_ENROLLED)->count(),
            'Pending' => (clone $query)->where('es.slug', EnrollmentStatus::PENDING)->count(),
            'Without a section' => (clone $query)->whereNull('e.section_ID')->count(),
        ];
        $breakdowns = [];
        foreach (['Enrollment status' => 'es.name', 'Grade level' => 'g.grade_label', 'Learner type' => 'lt.name', 'Sex' => 's.sex', 'Cluster' => 'cl.name', 'Section' => 'sec.name'] as $label => $column) {
            $fallback = match ($label) {
                'Section' => 'Unassigned',
                'Cluster' => 'No cluster / not applicable',
                default => 'Unspecified',
            };
            $breakdowns[$label] = (clone $query)->select($column.' as label')->selectRaw('COUNT(*) as total')
                ->groupBy($column)->orderByDesc('total')->orderBy($column)->get()
                ->map(fn ($row) => (object) ['label' => filled($row->label) ? ucfirst($row->label) : $fallback, 'total' => (int) $row->total]);
        }

        $breakdowns['Grade level'] = $breakdowns['Grade level']->sortBy('label', SORT_NATURAL)->values();
        $chartTitles = ['status' => 'Enrollment status', 'grade' => 'Grade level', 'learner' => 'Learner type', 'sex' => 'Sex'];
        $reportContext = [
            'School year: '.($years->firstWhere('SY_ID', $filters['academic_year_id'])?->school_year ?? 'All school years'),
            'Grade: '.(GradeLevel::query()->find($filters['grade_id'] ?? null)?->grade_label ?? 'All'),
            'Status: '.(EnrollmentStatus::options()[$filters['status'] ?? ''] ?? 'All'),
            'Learner type: '.(LearnerType::options()[$filters['learner_type'] ?? ''] ?? 'All'),
            'Section: '.ucfirst($filters['section'] ?? 'all'),
            'Search: '.($filters['search'] ?? 'None'),
        ];
        $generatedAt = now()->format('M d, Y, h:i A');

        $comparison = EnrollmentYearComparison::build($comparisonQuery, $years, $filters['academic_year_id']);
        $comparisonContext = $reportContext;
        $comparisonContext[0] = 'School years: '.$comparison['years']->pluck('school_year')->implode(', ');

        if (($filters['download'] ?? null) === 'comparison') {
            return response()->streamDownload(function () use ($comparison, $comparisonContext, $generatedAt) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                $write = function (array $cells) use ($stream) {
                    fputcsv($stream, array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $cells));
                };
                $write(['School-year comparison', 'Generated '.$generatedAt]);
                foreach ($comparisonContext as $line) {
                    $write([$line]);
                }
                $write(['Counts reflect current stored records, not enrollment at the same date in each year.']);
                $write(['School year', 'Records', 'Change from previous listed year', 'Change (%)']);
                foreach ($comparison['totals'] as $row) {
                    $write([$row->label, $row->total, $row->change ?? 'N/A', $row->percent ?? 'N/A']);
                }
                $write(['Grade level', ...$comparison['years']->pluck('school_year')->all()]);
                foreach ($comparison['grades'] as $row) {
                    $write([$row['label'], ...$row['counts']]);
                }
                fclose($stream);
            }, 'enrollment-year-comparison-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        if (($filters['download'] ?? null) === 'chart' && in_array($filters['chart'], ['year-trend', 'year-grade'], true)) {
            $svg = $filters['chart'] === 'year-trend'
                ? view('users.guidance.reports.partials.enrollment-chart', [
                    'title' => 'Enrollment by school year', 'rows' => $comparison['totals'],
                    'total' => $comparison['totals']->sum('total'), 'reportContext' => $comparisonContext,
                    'generatedAt' => $generatedAt,
                ])->render()
                : view('users.guidance.reports.partials.enrollment-year-grade-chart', compact('comparison', 'comparisonContext', 'generatedAt'))->render();

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="enrollment-'.$filters['chart'].'-'.now()->format('Y-m-d').'.svg"',
            ]);
        }

        if (($filters['download'] ?? null) === 'chart') {
            $title = $chartTitles[$filters['chart']];
            $svg = view('users.guidance.reports.partials.enrollment-chart', [
                'title' => $title, 'rows' => $breakdowns[$title], 'total' => $total,
                'reportContext' => $reportContext, 'generatedAt' => $generatedAt,
            ])->render();

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="enrollment-'.$filters['chart'].'-'.now()->format('Y-m-d').'.svg"',
            ]);
        }

        if (($filters['download'] ?? null) === 'summary') {
            return response()->streamDownload(function () use ($summary, $breakdowns, $total, $reportContext, $generatedAt) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                $write = function (array $cells) use ($stream) {
                    fputcsv($stream, array_map(function ($value) {
                        $value = (string) $value;

                        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
                    }, $cells));
                };
                $write(['Enrollment summary', 'Generated '.$generatedAt]);
                foreach ($reportContext as $line) {
                    $write([$line]);
                }
                $write(['Breakdowns count enrollment records, not unique learners.']);
                $write(['Summary metric', 'Count']);
                foreach ($summary as $label => $count) {
                    $write([$label, $count]);
                }
                $write(['Breakdown', 'Category', 'Records', 'Share (%)']);
                foreach ($breakdowns as $title => $rows) {
                    foreach ($rows as $row) {
                        $write([$title, $row->label, $row->total, $total ? round($row->total / $total * 100, 1) : 0]);
                    }
                }
                fclose($stream);
            }, 'enrollment-summary-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('users.guidance.reports.enrollment', [
            'years' => $years,
            'grades' => GradeLevel::query()->orderBy('grade_ID')->get(),
            'statuses' => EnrollmentStatus::options(),
            'learnerTypes' => LearnerType::options(),
            'filters' => $filters,
            'summary' => $summary,
            'total' => $total,
            'breakdowns' => $breakdowns,
            'chartTitles' => $chartTitles,
            'reportContext' => $reportContext,
            'generatedAt' => $generatedAt,
            'comparison' => $comparison,
            'comparisonContext' => $comparisonContext,
            'records' => $details->paginate(25)->withQueryString(),
        ]);
    }
}
