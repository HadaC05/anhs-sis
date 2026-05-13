<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Subject;
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
        $overviewCurriculumId = $request->integer('overview_curriculum_ID');

        $curriculums = Curriculum::query()
            ->when($curriculumSearch !== '', function ($query) use ($curriculumSearch): void {
                $query->where(function ($inner) use ($curriculumSearch): void {
                    $inner->where('name', 'like', "%{$curriculumSearch}%")
                        ->orWhere('description', 'like', "%{$curriculumSearch}%");
                });
            })
            ->when(in_array($curriculumStatus, ['active', 'inactive'], true), function ($query) use ($curriculumStatus): void {
                $query->where('status', $curriculumStatus === 'active');
            })
            ->orderByDesc('curriculum_ID')
            ->paginate($curriculumPerPage, ['*'], 'curriculum_page')
            ->withQueryString();

        $curriculumSubjects = CurriculumSubject::query()
            ->with(['curriculum', 'subject', 'cluster'])
            ->when($curriculumSubjectSearch !== '', function ($query) use ($curriculumSubjectSearch): void {
                $query->whereHas('subject', function ($subjectQuery) use ($curriculumSubjectSearch): void {
                    $subjectQuery->where('code', 'like', "%{$curriculumSubjectSearch}%")
                        ->orWhere('title', 'like', "%{$curriculumSubjectSearch}%");
                });
            })
            ->when($curriculumSubjectCurriculumId > 0, function ($query) use ($curriculumSubjectCurriculumId): void {
                $query->where('curriculum_ID', $curriculumSubjectCurriculumId);
            })
            ->when($curriculumSubjectClusterId > 0, function ($query) use ($curriculumSubjectClusterId): void {
                $query->where('cluster_ID', $curriculumSubjectClusterId);
            })
            ->when(in_array($curriculumSubjectGradeLevel, ['grade_11', 'grade_12'], true), function ($query) use ($curriculumSubjectGradeLevel): void {
                $query->where('grade_level', $curriculumSubjectGradeLevel);
            })
            ->when(in_array($curriculumSubjectSemester, ['first', 'second'], true), function ($query) use ($curriculumSubjectSemester): void {
                $query->where('semester', $curriculumSubjectSemester);
            })
            ->orderByDesc('curr_subj_ID')
            ->paginate($curriculumSubjectsPerPage, ['*'], 'curriculum_subjects_page')
            ->withQueryString();

        $curriculumOptions = Curriculum::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['curriculum_ID', 'name']);

        $subjects = Subject::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['subject_ID', 'cluster_ID', 'code', 'title']);

        $clusters = Cluster::query()
            ->orderBy('name')
            ->get(['cluster_ID', 'name']);

        $curriculumOverview = Curriculum::query()
            ->with([
                'curriculumSubjects' => function ($query): void {
                    $query->with(['subject', 'cluster'])
                        ->orderBy('grade_level')
                        ->orderBy('semester')
                        ->orderBy('subject_ID');
                },
            ])
            ->when($overviewCurriculumId > 0, function ($query) use ($overviewCurriculumId): void {
                $query->where('curriculum_ID', $overviewCurriculumId);
            })
            ->orderBy('name')
            ->get();

        return view('users.admin.curriculum-config', [
            'curriculums' => $curriculums,
            'curriculumSubjects' => $curriculumSubjects,
            'curriculumOptions' => $curriculumOptions,
            'subjects' => $subjects,
            'clusters' => $clusters,
            'curriculumOverview' => $curriculumOverview,
            'curriculumPerPage' => $curriculumPerPage,
            'curriculumSubjectsPerPage' => $curriculumSubjectsPerPage,
        ]);
    }

    public function storeCurriculum(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:curriculum,name'],
            'description' => ['nullable', 'string'],
        ]);

        Curriculum::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => true,
        ]);

        return back()->with('success', 'Curriculum created successfully.');
    }

    public function updateCurriculum(Request $request, Curriculum $curriculum): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('curriculum', 'name')->ignore($curriculum->curriculum_ID, 'curriculum_ID')],
            'description' => ['nullable', 'string'],
        ]);

        $curriculum->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Curriculum updated successfully.');
    }

    public function toggleCurriculumStatus(Curriculum $curriculum): RedirectResponse
    {
        $curriculum->update(['status' => ! $curriculum->status]);

        return back()->with('success', $curriculum->status
            ? 'Curriculum activated successfully.'
            : 'Curriculum archived successfully.');
    }

    public function storeCurriculumSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'curriculum_ID' => ['required', 'integer', Rule::exists('curriculum', 'curriculum_ID')->where('status', true)],
            'subject_ID' => ['required', 'integer', Rule::exists('subjects', 'subject_ID')->where('status', 'active')],
            'cluster_ID' => ['required', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'grade_level' => ['required', Rule::in(['grade_11', 'grade_12'])],
            'semester' => ['required', Rule::in(['first', 'second'])],
        ]);

        $subjectClusterId = Subject::query()
            ->where('subject_ID', $validated['subject_ID'])
            ->value('cluster_ID');

        if ((int) $subjectClusterId !== (int) $validated['cluster_ID']) {
            return back()->withErrors([
                'cluster_ID' => 'Selected cluster does not match the selected subject.',
            ]);
        }

        $duplicate = CurriculumSubject::query()
            ->where('curriculum_ID', $validated['curriculum_ID'])
            ->where('subject_ID', $validated['subject_ID'])
            ->where('grade_level', $validated['grade_level'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'subject_ID' => 'This subject is already assigned to the selected curriculum, grade level, and semester.',
            ]);
        }

        CurriculumSubject::query()->create($validated);

        return back()->with('success', 'Curriculum subject added successfully.');
    }

    public function updateCurriculumSubject(Request $request, CurriculumSubject $curriculumSubject): RedirectResponse
    {
        $validated = $request->validate([
            'curriculum_ID' => ['required', 'integer', Rule::exists('curriculum', 'curriculum_ID')->where('status', true)],
            'subject_ID' => ['required', 'integer', Rule::exists('subjects', 'subject_ID')->where('status', 'active')],
            'cluster_ID' => ['required', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'grade_level' => ['required', Rule::in(['grade_11', 'grade_12'])],
            'semester' => ['required', Rule::in(['first', 'second'])],
        ]);

        $subjectClusterId = Subject::query()
            ->where('subject_ID', $validated['subject_ID'])
            ->value('cluster_ID');

        if ((int) $subjectClusterId !== (int) $validated['cluster_ID']) {
            return back()->withErrors([
                'cluster_ID' => 'Selected cluster does not match the selected subject.',
            ]);
        }

        $duplicate = CurriculumSubject::query()
            ->where('curriculum_ID', $validated['curriculum_ID'])
            ->where('subject_ID', $validated['subject_ID'])
            ->where('grade_level', $validated['grade_level'])
            ->where('semester', $validated['semester'])
            ->where('curr_subj_ID', '!=', $curriculumSubject->curr_subj_ID)
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'subject_ID' => 'This subject is already assigned to the selected curriculum, grade level, and semester.',
            ]);
        }

        $curriculumSubject->update($validated);

        return back()->with('success', 'Curriculum subject updated successfully.');
    }

    public function destroyCurriculumSubject(CurriculumSubject $curriculumSubject): RedirectResponse
    {
        $curriculumSubject->delete();

        return back()->with('success', 'Curriculum subject removed successfully.');
    }
}
