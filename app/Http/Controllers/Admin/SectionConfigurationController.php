<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $status = $request->string('status')->toString();
        if ($status === '') {
            $status = 'active';
        }

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
            ->when($status === 'active', function ($query): void {
                $query->where('status', true);
            })
            ->when($status === 'inactive', function ($query): void {
                $query->where('status', false);
            })
            ->orderByDesc('status')
            ->orderByDesc('section_ID')
            ->paginate($perPage)
            ->withQueryString();

        $clusters = Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);
        $academicYears = AcademicYear::query()->orderByDesc('SY_ID')->get(['SY_ID', 'school_year', 'status']);
        $curriculums = Curriculum::query()
            ->whereHas('dataStatus', fn ($status) => $status->where('key', 'active'))
            ->orderBy('name')
            ->get(['curriculum_ID', 'name']);
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
            'status' => $status,
        ]);
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        $validated = $this->sectionAttributes($request->validated());
        $validated['status'] = true;

        Section::query()->create($validated);

        return back()->with('success', 'Section created successfully.');
    }

    public function update(StoreSectionRequest $request, Section $section): RedirectResponse
    {
        $section->update($this->sectionAttributes($request->validated()));

        return back()->with('success', 'Section updated successfully.');
    }

    public function toggleStatus(Section $section): RedirectResponse
    {
        if ($section->status) {
            $section->update(['status' => false]);

            return back()->with('success', 'Section archived successfully.');
        }

        $section->update(['status' => true]);

        return back()->with('success', 'Section activated successfully.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function sectionAttributes(array $validated): array
    {
        $offering = Curriculum::query()->findOrFail($validated['curriculum_ID']);

        // A section always belongs to one curriculum-grade-level offering.
        // Copy its context rather than trusting duplicated form values.
        $validated['grade_ID'] = $offering->grade_ID;
        $validated['cluster_ID'] = $offering->cluster_ID;
        unset($validated['grade_level']);

        return $validated;
    }
}
