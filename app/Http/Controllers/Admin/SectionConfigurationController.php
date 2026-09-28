<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopySectionsRequest;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $curriculumGradeLevelId = $request->integer('curriculum_grade_level_ID');
        $status = $request->string('status')->toString();
        if ($status === '') {
            $status = 'active';
        }

        $gradeId = GradeLevel::idForValue($gradeLevel);

        $sections = Section::query()
            ->with(['cluster', 'gradeLevel', 'adviser', 'academicYear', 'curriculumGradeLevel.gradeLevel', 'curriculumGradeLevel.gradingSemester'])
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
            ->when($curriculumGradeLevelId > 0, function ($query) use ($curriculumGradeLevelId): void {
                $query->where('curriculum_grade_level_ID', $curriculumGradeLevelId);
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

    public function copy(CopySectionsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        [$created, $skipped] = DB::transaction(function () use ($data): array {
            // Serialize copy requests targeting the same school year.
            AcademicYear::query()->whereKey($data['target_SY_ID'])->lockForUpdate()->firstOrFail();

            $sources = Section::query()
                ->where('SY_ID', $data['source_SY_ID'])
                ->when($data['copy_grade_level'] !== 'all', fn ($query) => $query
                    ->where('grade_ID', GradeLevel::idForValue($data['copy_grade_level'])))
                ->get();

            if ($sources->isEmpty()) {
                throw ValidationException::withMessages([
                    'source_SY_ID' => 'No sections found for the selected source school year and grade level.',
                ])->errorBag('copySections');
            }

            $created = 0;
            $skipped = 0;
            foreach ($sources as $source) {
                // Use the database comparison to respect its name collation.
                if (Section::query()->where('SY_ID', $data['target_SY_ID'])->where('name', $source->name)->exists()) {
                    $skipped++;

                    continue;
                }

                Section::query()->create([
                    'name' => $source->name,
                    'grade_ID' => $source->grade_ID,
                    'cluster_ID' => $source->cluster_ID,
                    'curriculum_grade_level_ID' => $source->curriculum_grade_level_ID,
                    'room' => $source->room,
                    'capacity' => $source->capacity,
                    'SY_ID' => $data['target_SY_ID'],
                    'staff_ID' => $data['adviser_mode'] === 'keep' ? $source->staff_ID : null,
                    'status' => true,
                ]);
                $created++;
            }

            return [$created, $skipped];
        });

        $prefix = $request->routeIs('principal.*') ? 'principal.' : 'admin.';

        return redirect()->route($prefix.'section-config.index', ['SY_ID' => $data['target_SY_ID']])
            ->with('success', "Copied {$created} section(s). Skipped {$skipped} section(s) already present in the destination school year.");
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
        $offering = Curriculum::query()->findOrFail($validated['curriculum_grade_level_ID']);

        // A section always belongs to one curriculum-grade-level offering.
        // Copy its context rather than trusting duplicated form values.
        $validated['grade_ID'] = $offering->grade_ID;
        $validated['cluster_ID'] = $offering->cluster_ID;
        unset($validated['grade_level']);

        return $validated;
    }
}
