<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SectionConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [5, 10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $search = trim($request->string('search')->toString());
        $clusterId = $request->integer('cluster_ID');
        $gradeLevel = $request->string('grade_level')->toString();
        $syId = $request->integer('SY_ID');
        $curriculumId = $request->integer('curriculum_ID');

        $gradeId = GradeLevel::idForValue($gradeLevel);

        $sections = Section::query()
            ->with(['cluster', 'gradeLevel', 'adviser', 'academicYear', 'curriculum'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('room', 'like', "%{$search}%");
                });
            })
            ->when($clusterId > 0, function ($query) use ($clusterId): void {
                $query->where('cluster_ID', $clusterId);
            })
            ->when($gradeId, function ($query) use ($gradeId): void {
                $query->where('grade_ID', $gradeId);
            })
            ->when($syId > 0, function ($query) use ($syId): void {
                $query->where('SY_ID', $syId);
            })
            ->when($curriculumId > 0, function ($query) use ($curriculumId): void {
                $query->where('curriculum_ID', $curriculumId);
            })
            ->orderByDesc('section_ID')
            ->paginate($perPage)
            ->withQueryString();

        $clusters = Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);
        $academicYears = AcademicYear::query()->orderByDesc('SY_ID')->get(['SY_ID', 'school_year', 'status']);
        $curriculums = Curriculum::query()->where('status', true)->orderBy('name')->get(['curriculum_ID', 'name']);
        $staffs = Staff::query()
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['staff_id', 'first_name', 'middle_name', 'last_name', 'suffix']);

        return view('users.admin.section-config', [
            'sections' => $sections,
            'clusters' => $clusters,
            'academicYears' => $academicYears,
            'curriculums' => $curriculums,
            'staffs' => $staffs,
            'gradeLevels' => GradeLevel::options(),
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $syId = $request->integer('SY_ID');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')->where(fn ($query) => $query->where('SY_ID', $syId)),
            ],
            'cluster_ID' => ['nullable', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'grade_level' => ['required', Rule::in(['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])],
            'staff_ID' => ['nullable', 'integer', Rule::exists('staffs', 'staff_id')],
            'SY_ID' => ['required', 'integer', Rule::exists('academic_years', 'SY_ID')],
            'curriculum_ID' => ['required', 'integer', Rule::exists('curriculum', 'curriculum_ID')],
            'room' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
        ]);

        $validated['grade_ID'] = GradeLevel::idForValue($validated['grade_level']);
        $isSeniorHigh = in_array($validated['grade_level'], ['grade_11', 'grade_12'], true);
        if ($isSeniorHigh && empty($validated['cluster_ID'])) {
            return back()->withErrors(['cluster_ID' => 'Cluster is required for senior high school sections.'])->withInput();
        }
        if (! $isSeniorHigh) {
            $validated['cluster_ID'] = null;
        }
        unset($validated['grade_level']);

        Section::query()->create($validated);

        return back()->with('success', 'Section created successfully.');
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $syId = $request->integer('SY_ID');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')
                    ->where(fn ($query) => $query->where('SY_ID', $syId))
                    ->ignore($section->section_ID, 'section_ID'),
            ],
            'cluster_ID' => ['nullable', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'grade_level' => ['required', Rule::in(['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])],
            'staff_ID' => ['nullable', 'integer', Rule::exists('staffs', 'staff_id')],
            'SY_ID' => ['required', 'integer', Rule::exists('academic_years', 'SY_ID')],
            'curriculum_ID' => ['required', 'integer', Rule::exists('curriculum', 'curriculum_ID')],
            'room' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
        ]);

        $validated['grade_ID'] = GradeLevel::idForValue($validated['grade_level']);
        $isSeniorHigh = in_array($validated['grade_level'], ['grade_11', 'grade_12'], true);
        if ($isSeniorHigh && empty($validated['cluster_ID'])) {
            return back()->withErrors(['cluster_ID' => 'Cluster is required for senior high school sections.'])->withInput();
        }
        if (! $isSeniorHigh) {
            $validated['cluster_ID'] = null;
        }
        unset($validated['grade_level']);

        $section->update($validated);

        return back()->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Section deleted successfully.');
    }
}
