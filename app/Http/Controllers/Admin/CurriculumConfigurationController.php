<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCurriculumSubjectRequest;
use App\Models\Cluster;
use App\Models\Curricula;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\DataStatus;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
use App\Models\Subject;
use App\Models\SubjectType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CurriculumConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $curriculumPerPage = (int) $request->integer('curriculum_per_page', 10);
        if (! in_array($curriculumPerPage, [5, 10, 15, 25, 50], true)) {
            $curriculumPerPage = 10;
        }

        $curriculumSubjectsPerPage = (int) $request->integer('curriculum_subjects_per_page', 10);
        if (! in_array($curriculumSubjectsPerPage, [5, 10, 15, 25, 50], true)) {
            $curriculumSubjectsPerPage = 10;
        }

        $curriculumSearch = trim($request->string('curriculum_search')->toString());
        $curriculumStatus = $request->string('curriculum_status')->toString();

        $curriculumSubjectSearch = trim($request->string('curriculum_subjects_search')->toString());
        $curriculumSubjectCurriculumId = $request->integer('curriculum_subjects_curriculum_ID');
        $curriculumSubjectClusterId = $request->integer('curriculum_subjects_cluster_ID');
        $curriculumSubjectGradeLevel = $request->string('curriculum_subjects_grade_level')->toString();
        $curriculumSubjectSemester = $request->string('curriculum_subjects_semester')->toString();
        $curriculumSubjectSemesterId = (int) $request->integer('curriculum_subjects_semester');
        $overviewCurriculumId = $request->integer('overview_curriculum_ID');
        $gradeLevelOptions = GradeLevel::options();
        $curriculumSubjectGradeId = GradeLevel::idForValue($curriculumSubjectGradeLevel);

        $curriculums = Curriculum::query()
            ->when($curriculumSearch !== '', function ($query) use ($curriculumSearch): void {
                $query->where(function ($inner) use ($curriculumSearch): void {
                    $inner->where('name', 'like', "%{$curriculumSearch}%")
                        ->orWhere('description', 'like', "%{$curriculumSearch}%");
                });
            })
            ->when(in_array($curriculumStatus, ['active', 'inactive'], true), function ($query) use ($curriculumStatus): void {
                $query->whereHas('dataStatus', fn ($status) => $status->where('key', $curriculumStatus === 'active' ? 'active' : 'archived'));
            })
            ->orderBy('grade_ID')
            ->orderBy('semester_ID')
            ->orderBy('curriculum_ID')
            ->paginate($curriculumPerPage, ['*'], 'curriculum_page')
            ->withQueryString();

        $curriculumSubjects = CurriculumSubject::query()
            ->with(['curriculum', 'subject', 'cluster', 'gradeLevel', 'gradingSemester'])
            ->when($curriculumSubjectSearch !== '', function ($query) use ($curriculumSubjectSearch): void {
                $query->whereHas('subject', function ($subjectQuery) use ($curriculumSubjectSearch): void {
                    $subjectQuery->where('code', 'like', "%{$curriculumSubjectSearch}%")
                        ->orWhere('title', 'like', "%{$curriculumSubjectSearch}%");
                });
            })
            ->when($curriculumSubjectCurriculumId > 0, function ($query) use ($curriculumSubjectCurriculumId): void {
                $query->where('curriculum_grade_level_ID', $curriculumSubjectCurriculumId);
            })
            ->when($curriculumSubjectClusterId > 0, function ($query) use ($curriculumSubjectClusterId): void {
                $query->whereHas('curriculumGradeLevel', fn ($offering) => $offering->where('cluster_ID', $curriculumSubjectClusterId));
            })
            ->when($curriculumSubjectGradeId, function ($query) use ($curriculumSubjectGradeId): void {
                $query->whereHas('curriculumGradeLevel', fn ($offering) => $offering->where('grade_ID', $curriculumSubjectGradeId));
            })
            ->when($curriculumSubjectSemesterId > 0, function ($query) use ($curriculumSubjectSemesterId): void {
                $query->whereHas('curriculumGradeLevel', fn ($offering) => $offering->where('semester_ID', $curriculumSubjectSemesterId));
            })
            ->orderByDesc('curr_subj_ID')
            ->paginate($curriculumSubjectsPerPage, ['*'], 'curriculum_subjects_page')
            ->withQueryString();

        $curriculumSubjectCurriculums = Curriculum::query()
            ->whereHas('curriculumSubjects', function ($query) use ($curriculumSubjectSearch): void {
                $query
                    ->when($curriculumSubjectSearch !== '', fn ($subjectQuery) => $subjectQuery->whereHas('subject', fn ($itemQuery) => $itemQuery->where('code', 'like', "%{$curriculumSubjectSearch}%")->orWhere('title', 'like', "%{$curriculumSubjectSearch}%")));
            })
            ->with(['curriculumSubjects' => function ($query) use ($curriculumSubjectSearch): void {
                $query->with(['subject', 'cluster', 'gradeLevel', 'gradingSemester'])
                    ->when($curriculumSubjectSearch !== '', fn ($subjectQuery) => $subjectQuery->whereHas('subject', fn ($itemQuery) => $itemQuery->where('code', 'like', "%{$curriculumSubjectSearch}%")->orWhere('title', 'like', "%{$curriculumSubjectSearch}%")))
                    ->orderBy('subject_ID');
            }])
            ->when($curriculumSubjectCurriculumId > 0, fn ($query) => $query->where('curriculum_ID', $curriculumSubjectCurriculumId))
            ->when($curriculumSubjectClusterId > 0, fn ($query) => $query->where('cluster_ID', $curriculumSubjectClusterId))
            ->when($curriculumSubjectGradeId, fn ($query) => $query->where('grade_ID', $curriculumSubjectGradeId))
            ->when($curriculumSubjectSemesterId > 0, fn ($query) => $query->where('semester_ID', $curriculumSubjectSemesterId))
            ->orderBy('grade_ID')
            ->orderBy('semester_ID')
            ->orderBy('cluster_ID')
            ->orderBy('name')
            ->paginate($curriculumSubjectsPerPage, ['*'], 'curriculum_subjects_page')
            ->withQueryString();

        $curriculumOptions = Curriculum::query()
            ->whereHas('dataStatus', fn ($status) => $status->where('key', 'active'))
            ->orderBy('grade_ID')
            ->orderBy('semester_ID')
            ->orderBy('cluster_ID')
            ->get(['curriculum_ID', 'name', 'grade_ID', 'semester_ID', 'cluster_ID']);

        $unassignedCurriculumOptions = Curriculum::query()
            ->whereHas('dataStatus', fn ($status) => $status->where('key', 'active'))
            ->whereDoesntHave('curriculumSubjects')
            ->with('gradeLevel')
            ->orderBy('grade_ID')
            ->orderBy('semester_ID')
            ->orderBy('cluster_ID')
            ->get(['curriculum_ID', 'name', 'grade_ID', 'semester_ID', 'cluster_ID']);

        $subjects = Subject::query()
            ->with('subjectType')
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['subject_ID', 'school_level', 'subject_type_ID', 'code', 'title']);

        $clusters = Cluster::query()
            ->orderBy('name')
            ->get(['cluster_ID', 'name']);

        $curriculumOverview = Curriculum::query()
            ->with([
                'curriculumSubjects' => function ($query): void {
                    $query->with(['subject', 'cluster', 'gradeLevel', 'gradingSemester'])
                        ->orderBy('subject_ID');
                },
            ])
            ->when($overviewCurriculumId > 0, function ($query) use ($overviewCurriculumId): void {
                $query->where('curriculum_ID', $overviewCurriculumId);
            })
            ->orderBy('grade_ID')
            ->orderBy('semester_ID')
            ->orderBy('cluster_ID')
            ->orderBy('name')
            ->get();

        return view('users.admin.curriculum-config', [
            'masterCurricula' => Curricula::query()->with('dataStatus')->orderBy('name')->get(),
            'activeMasterCurricula' => Curricula::query()->whereHas('dataStatus', fn ($status) => $status->where('key', 'active'))->orderBy('name')->get(),
            'curriculums' => $curriculums,
            'curriculumSubjects' => $curriculumSubjects,
            'curriculumSubjectCurriculums' => $curriculumSubjectCurriculums,
            'curriculumOptions' => $curriculumOptions,
            'unassignedCurriculumOptions' => $unassignedCurriculumOptions,
            'subjects' => $subjects,
            'subjectTypes' => SubjectType::query()->orderBy('sort_order')->get(['subject_type_ID', 'key', 'label']),
            'clusters' => $clusters,
            'curriculumOverview' => $curriculumOverview,
            'curriculumPerPage' => $curriculumPerPage,
            'curriculumSubjectsPerPage' => $curriculumSubjectsPerPage,
            'gradeLevelOptions' => $gradeLevelOptions,
            'gradingSemesters' => GradingSemester::query()->orderBy('sort_order')->get(),
            'totalCurriculums' => Curriculum::query()->count(),
            'activeCurriculumCount' => Curriculum::query()->whereHas('dataStatus', fn ($status) => $status->where('key', 'active'))->count(),
            'inactiveCurriculumCount' => Curriculum::query()->whereHas('dataStatus', fn ($status) => $status->where('key', 'archived'))->count(),
            'curriculumSubjectCount' => CurriculumSubject::query()->count(),
        ]);
    }

    public function storeCurriculum(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'curricula_ID' => ['required', 'integer', 'exists:curricula,curricula_ID'],
            'grade_ID' => ['required', 'integer', 'exists:grade_level,grade_ID'],
            'semester_ID' => ['required', 'integer', 'exists:grading_semesters,semester_ID'],
            'cluster_ID' => [
                Rule::requiredIf(fn () => GradeLevel::query()->whereKey($request->integer('grade_ID'))->value('category') === 'Senior High School'),
                'nullable', 'integer', 'exists:clusters,cluster_ID',
            ],
            'name' => ['required', 'string', 'max:255', 'unique:curriculum_grade_levels,name'],
        ]);

        Curriculum::query()->create($validated + ['data_status_ID' => DataStatus::query()->where('key', 'active')->value('data_status_ID')]);

        return back()->with('show_toast', true);
    }

    public function storeMasterCurriculum(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:curricula,name'], 'description' => ['nullable', 'string']]);
        Curricula::query()->create($validated + ['data_status_ID' => DataStatus::query()->where('key', 'active')->value('data_status_ID')]);

        return back()->with('success', 'Curriculum created successfully.');
    }

    public function updateMasterCurriculum(Request $request, Curricula $curricula): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('curricula', 'name')->ignore($curricula->curricula_ID, 'curricula_ID')], 'description' => ['nullable', 'string']]);
        $curricula->update($validated);

        return back()->with('show_toast', true);
    }

    public function toggleMasterCurriculumStatus(Curricula $curricula): RedirectResponse
    {
        $current = $curricula->dataStatus?->key;
        $curricula->update(['data_status_ID' => DataStatus::query()->where('key', $current === 'active' ? 'archived' : 'active')->value('data_status_ID')]);

        return back()->with('success', 'Curriculum status updated successfully.');
    }

    public function curriculumReport(Request $request, Curricula $curricula)
    {
        $curricula->load([
            'curriculumGradeLevels.gradeLevel',
            'curriculumGradeLevels.gradingSemester',
            'curriculumGradeLevels.cluster',
            'curriculumGradeLevels.curriculumSubjects.subject.subjectType',
        ]);

        $gradeLevels = $curricula->curriculumGradeLevels
            ->sortBy(fn (Curriculum $gradeLevel) => [$gradeLevel->grade_ID, $gradeLevel->semester_ID, $gradeLevel->cluster_ID])
            ->values();

        $response = response()->view('users.admin.curriculum-report', compact('curricula', 'gradeLevels'));

        if ($request->boolean('download')) {
            $response->header('Content-Disposition', 'attachment; filename="'.str($curricula->name)->slug().'-curriculum-summary.html"');
        }

        return $response;
    }

    public function updateCurriculum(Request $request, Curriculum $curriculum): RedirectResponse
    {
        $validated = $request->validate([
            'curricula_ID' => ['required', 'integer', 'exists:curricula,curricula_ID'],
            'grade_ID' => ['required', 'integer', 'exists:grade_level,grade_ID'],
            'semester_ID' => ['required', 'integer', 'exists:grading_semesters,semester_ID'],
            'cluster_ID' => [
                Rule::requiredIf(fn () => GradeLevel::query()->whereKey($request->integer('grade_ID'))->value('category') === 'Senior High School'),
                'nullable', 'integer', 'exists:clusters,cluster_ID',
            ],
            'name' => ['required', 'string', 'max:255', Rule::unique('curriculum_grade_levels', 'name')->ignore($curriculum->curriculum_ID, 'curriculum_ID')],
        ]);

        $curriculum->update($validated);

        return back()->with('show_toast', true);
    }

    public function toggleCurriculumStatus(Curriculum $curriculum): RedirectResponse
    {
        $nextStatus = $curriculum->status ? 'archived' : 'active';
        $curriculum->update(['data_status_ID' => DataStatus::query()->where('key', $nextStatus)->value('data_status_ID')]);
        $curriculum->refresh();

        return back()->with('success', $curriculum->status
            ? 'Curriculum activated successfully.'
            : 'Curriculum archived successfully.');
    }

    public function storeCurriculumSubject(StoreCurriculumSubjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $subjectIds = $validated['subject_ID'];
        unset($validated['subject_ID']);

        $isEditing = $request->boolean('edit_mode');
        unset($validated['edit_mode']);

        foreach ($subjectIds as $subjectId) {
            $assignment = [
                'curriculum_grade_level_ID' => $validated['curriculum_ID'],
                'subject_ID' => $subjectId,
            ];

            if ($isEditing) {
                CurriculumSubject::query()->firstOrCreate($assignment);
            } else {
                CurriculumSubject::query()->create($assignment);
            }
        }

        return back()
            ->with('success', 'Subjects assigned successfully.')
            ->with('show_toast', true);
    }

    public function updateCurriculumSubject(StoreCurriculumSubjectRequest $request, CurriculumSubject $curriculumSubject): RedirectResponse
    {
        $validated = $request->validated();
        $validated['subject_ID'] = $validated['subject_ID'][0];

        $curriculumSubject->update($validated);

        return back()->with('success', 'Curriculum subject updated successfully.');
    }

    public function destroyCurriculumSubject(CurriculumSubject $curriculumSubject): RedirectResponse
    {
        $curriculumSubject->delete();

        return back()->with('success', 'Curriculum subject removed successfully.');
    }
}
