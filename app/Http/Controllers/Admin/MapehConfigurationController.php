<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\MapehConfiguration;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Support\MapehSetup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MapehConfigurationController extends Controller
{
    public function edit(Request $request, Curriculum $curriculum)
    {
        abort_unless($curriculum->gradeLevel?->category === 'Junior High School', 404);
        $years = AcademicYear::query()->orderByDesc('school_year')->get();
        $year = $years->firstWhere('SY_ID', $request->integer('SY_ID')) ?? $years->firstWhere('status', true) ?? $years->first();
        $configuration = MapehConfiguration::query()->with(['components.curriculumSubject.subject', 'parentSubject.subject'])
            ->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)->where('SY_ID', $year?->SY_ID)->first();
        $parents = $curriculum->curriculumSubjects()->with('subject')->get()->filter(fn ($row) => MapehSetup::isParentSubject($row->subject));
        $subjects = Subject::query()->where('school_level', 'Junior High School')->where('status', 'active')->orderBy('title')->get();
        $sections = Section::query()->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)->where('SY_ID', $year?->SY_ID)->orderBy('name')->get();
        $assignments = TeacherSubjectAssignment::query()->with('staff')->whereIn('section_ID', $sections->pluck('section_ID'))
            ->where('SY_ID', $year?->SY_ID)->get()->groupBy('section_ID');

        return view('users.admin.mapeh-config', compact('curriculum', 'years', 'year', 'configuration', 'parents', 'subjects', 'sections', 'assignments'));
    }

    public function store(Request $request, Curriculum $curriculum)
    {
        $data = $request->validate([
            'SY_ID' => ['required', 'integer', 'exists:academic_years,SY_ID'],
            'parent_curr_subj_ID' => ['required', 'integer', Rule::exists('curriculum_subjects', 'curr_subj_ID')->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)],
            'mode' => ['required', Rule::in(['four', 'paired'])],
            'components' => ['nullable', 'array'],
            'components.*' => ['nullable', 'integer', 'exists:subjects,subject_ID'],
        ]);
        MapehSetup::save($curriculum, $data);
        $prefix = $request->routeIs('principal.*') ? 'principal.' : 'admin.';

        return redirect()->route($prefix.'curriculum-config.mapeh.edit', ['curriculum' => $curriculum, 'SY_ID' => $data['SY_ID']])
            ->with('status', 'MAPEH components configured. Assign a teacher to each component for every class.');
    }
}
