<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\CurriculumSubject;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $clusterId = $request->string('cluster_ID')->toString();
        $schoolYearId = $request->string('SY_ID')->toString();
        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        $assignments = TeacherSubjectAssignment::query()
            ->with(['section.cluster', 'section.gradeLevel', 'section.academicYear', 'curriculumSubject.subject', 'staff.user'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('section', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('curriculumSubject.subject', function ($q) use ($search): void {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('staff', function ($q) use ($search): void {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($gradeId, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('grade_ID', $gradeId)))
            ->when($clusterId !== '', fn ($q) => $q->whereHas('section', fn ($s) => $s->where('cluster_ID', $clusterId)))
            ->when($schoolYearId !== '', fn ($q) => $q->where('SY_ID', $schoolYearId))
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();

        $sections = Section::query()
            ->with(['cluster', 'gradeLevel', 'academicYear'])
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->get();

        $curriculumSubjects = CurriculumSubject::query()
            ->with('subject')
            ->orderBy('grade_level')
            ->orderBy('semester')
            ->get();

        $teachers = Staff::query()
            ->whereHas('user.role', fn ($q) => $q->where('role_name', 'teacher'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $academicYears = AcademicYear::query()->orderByDesc('school_year')->get(['SY_ID', 'school_year']);
        $clusters = Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);

        return view('users.admin.teacher-assignments', [
            'assignments' => $assignments,
            'sections' => $sections,
            'curriculumSubjects' => $curriculumSubjects,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
            'clusters' => $clusters,
            'gradeLevels' => GradeLevel::options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_ID' => ['required', 'integer', 'exists:sections,section_ID'],
            'curr_subj_ID' => ['required', 'integer', 'exists:curriculum_subjects,curr_subj_ID'],
            'staff_ID' => ['required', 'integer', 'exists:staffs,staff_id'],
        ]);

        $section = Section::query()->findOrFail($validated['section_ID']);
        $curriculumSubject = CurriculumSubject::query()->findOrFail($validated['curr_subj_ID']);
        $teacher = Staff::query()
            ->where('staff_id', $validated['staff_ID'])
            ->whereHas('user.role', fn ($q) => $q->where('role_name', 'teacher'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'staff_ID' => 'Selected staff is not a teacher.',
            ]);
        }

        if (! $this->curriculumSubjectMatchesSection($section, $curriculumSubject)) {
            throw ValidationException::withMessages([
                'curr_subj_ID' => 'Selected subject does not belong to the section curriculum and grade level.',
            ]);
        }

        TeacherSubjectAssignment::query()->updateOrCreate(
            [
                'section_ID' => $section->section_ID,
                'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
            ],
            [
                'staff_ID' => $validated['staff_ID'],
                'SY_ID' => $section->SY_ID,
            ]
        );

        return back()->with('status', 'Assignment saved successfully.');
    }

    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'grade_levels' => ['required', 'array', 'min:1'],
            'grade_levels.*' => ['required', 'in:grade_7,grade_8,grade_9,grade_10,grade_11,grade_12'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['required', 'integer', 'exists:sections,section_ID'],
            'curr_subj_ids' => ['required', 'array', 'min:1'],
            'curr_subj_ids.*' => ['required', 'integer', 'exists:curriculum_subjects,curr_subj_ID'],
            'staff_ID' => ['required', 'integer', 'exists:staffs,staff_id'],
        ]);

        $teacher = Staff::query()
            ->where('staff_id', $validated['staff_ID'])
            ->whereHas('user.role', fn ($q) => $q->where('role_name', 'teacher'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'staff_ID' => 'Selected staff is not a teacher.',
            ]);
        }

        $sections = Section::query()
            ->whereIn('section_ID', $validated['section_ids'])
            ->get()
            ->keyBy('section_ID');

        $subjects = CurriculumSubject::query()
            ->whereIn('curr_subj_ID', $validated['curr_subj_ids'])
            ->get()
            ->keyBy('curr_subj_ID');

        if ($sections->count() !== count(array_unique($validated['section_ids']))) {
            throw ValidationException::withMessages([
                'section_ids' => 'One or more selected sections could not be found.',
            ]);
        }

        if ($subjects->count() !== count(array_unique($validated['curr_subj_ids']))) {
            throw ValidationException::withMessages([
                'curr_subj_ids' => 'One or more selected subjects could not be found.',
            ]);
        }

        $selectedGradeLevels = collect($validated['grade_levels']);
        $invalidSectionSelected = $sections->contains(function (Section $section) use ($selectedGradeLevels): bool {
            return ! $selectedGradeLevels->contains($section->grade_level);
        });

        if ($invalidSectionSelected) {
            throw ValidationException::withMessages([
                'section_ids' => 'Selected sections must belong to the checked grade levels.',
            ]);
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($sections as $section) {
            foreach ($subjects as $subject) {
                if (! $this->curriculumSubjectMatchesSection($section, $subject)) {
                    $skipped++;
                    continue;
                }

                TeacherSubjectAssignment::query()->updateOrCreate(
                    [
                        'section_ID' => $section->section_ID,
                        'curr_subj_ID' => $subject->curr_subj_ID,
                    ],
                    [
                        'staff_ID' => $teacher->staff_id,
                        'SY_ID' => $section->SY_ID,
                    ]
                );
                $assigned++;
            }
        }

        if ($assigned === 0) {
            throw ValidationException::withMessages([
                'curr_subj_ids' => 'No compatible section and subject combinations were found for the selected bulk assignment.',
            ]);
        }

        $message = "Bulk assignment completed ({$assigned} assignment(s)).";
        if ($skipped > 0) {
            $message .= " {$skipped} incompatible combination(s) were skipped.";
        }

        return back()->with('status', $message);
    }

    public function update(Request $request, TeacherSubjectAssignment $assignment)
    {
        $validated = $request->validate([
            'staff_ID' => ['required', 'integer', 'exists:staffs,staff_id'],
        ]);

        $teacher = Staff::query()
            ->where('staff_id', $validated['staff_ID'])
            ->whereHas('user.role', fn ($q) => $q->where('role_name', 'teacher'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'staff_ID' => 'Selected staff is not a teacher.',
            ]);
        }

        $assignment->update([
            'staff_ID' => $validated['staff_ID'],
        ]);

        return back()->with('status', 'Assignment updated successfully.');
    }

    public function destroy(TeacherSubjectAssignment $assignment)
    {
        $assignment->delete();

        return back()->with('status', 'Assignment removed.');
    }

    private function curriculumSubjectMatchesSection(Section $section, CurriculumSubject $curriculumSubject): bool
    {
        if ($curriculumSubject->curriculum_ID !== $section->curriculum_ID ||
            $curriculumSubject->grade_level !== $section->grade_level) {
            return false;
        }

        if ($section->cluster_ID && $curriculumSubject->cluster_ID && $section->cluster_ID !== $curriculumSubject->cluster_ID) {
            return false;
        }

        return true;
    }
}
