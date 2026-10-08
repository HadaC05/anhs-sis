<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Cluster;
use App\Models\PreferredCourse;
use App\Models\Subject;
use App\Models\SubjectType;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->integer('per_page', 15);
        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();
        $schoolLevel = $request->string('school_level')->toString();
        $clusterId = $request->integer('cluster_ID');
        $trackSearch = trim($request->string('track_search')->toString());
        $clusterSearch = trim($request->string('cluster_search')->toString());
        $clusterTrackId = $request->integer('cluster_track_ID');
        $subjects = Subject::query()
            ->with(['subjectType', 'cluster'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'archived'], true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when(SubjectType::idForKey($type), function ($query) use ($type): void {
                $query->whereHas('subjectType', fn ($typeQuery) => $typeQuery->where('key', $type));
            })
            ->when(in_array($schoolLevel, ['Junior High School', 'Senior High School'], true), function ($query) use ($schoolLevel): void {
                $query->where('school_level', $schoolLevel);
            })
            ->when($clusterId > 0, function ($query) use ($clusterId): void {
                $query->where('cluster_ID', $clusterId);
            })
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        $tracks = Track::query()
            ->withCount('clusters')
            ->when($trackSearch !== '', fn ($query) => $query->where('name', 'like', "%{$trackSearch}%"))
            ->orderBy('name')
            ->get();

        $clusters = Cluster::query()
            ->with('track')
            ->withCount('subjects')
            ->when($clusterSearch !== '', fn ($query) => $query->where('name', 'like', "%{$clusterSearch}%"))
            ->when($clusterTrackId > 0, fn ($query) => $query->where('track_ID', $clusterTrackId))
            ->orderBy('name')
            ->get();

        $allTracks = Track::query()->orderBy('name')->get(['track_ID', 'name']);
        $allClusters = Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);

        return view('users.admin.subject-config', [
            'subjects' => $subjects,
            'tracks' => $tracks,
            'clusters' => $clusters,
            'allTracks' => $allTracks,
            'subjectTypes' => SubjectType::query()->orderBy('sort_order')->get(['subject_type_ID', 'key', 'label']),
            'subjectClusters' => $allClusters,
            'perPage' => $perPage,
            'trackCount' => Track::query()->count(),
            'clusterCount' => Cluster::query()->count(),
            'totalSubjects' => Subject::query()->count(),
            'activeSubjectCount' => Subject::query()->where('status', 'active')->count(),
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        Subject::query()->create($request->validated());

        return back()->with('success', 'Subject created successfully.');
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return back()->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $nextStatus = $subject->status === 'archived' ? 'active' : 'archived';
        $subject->update(['status' => $nextStatus]);

        return back()->with('success', $nextStatus === 'archived'
            ? 'Subject archived successfully.'
            : 'Subject restored successfully.');
    }

    public function storeTrack(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tracks', 'name')],
        ]);

        Track::query()->create($validated);

        return back()->with('success', 'Track created successfully.');
    }

    public function updateTrack(Request $request, Track $track): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tracks', 'name')->ignore($track->track_ID, 'track_ID')],
        ]);

        $track->update($validated);

        return back()->with('success', 'Track updated successfully.');
    }

    public function storeCluster(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'track_ID' => ['required', 'integer', Rule::exists('tracks', 'track_ID')],
            'name' => ['required', 'string', 'max:255', Rule::unique('clusters', 'name')],
        ]);

        Cluster::query()->create($validated);

        return back()->with('success', 'Cluster created successfully.');
    }

    public function updateCluster(Request $request, Cluster $cluster): RedirectResponse
    {
        $validated = $request->validate([
            'track_ID' => ['required', 'integer', Rule::exists('tracks', 'track_ID')],
            'name' => ['required', 'string', 'max:255', Rule::unique('clusters', 'name')->ignore($cluster->cluster_ID, 'cluster_ID')],
        ]);

        $cluster->update($validated);

        return back()->with('success', 'Cluster updated successfully.');
    }

    public function storePreferredCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cluster_ID' => ['required', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('preferred_courses', 'name')->where(fn ($query) => $query->where('cluster_ID', $request->integer('cluster_ID'))),
            ],
            'description' => ['nullable', 'string'],
        ]);

        PreferredCourse::query()->create($validated);

        return back()->with('success', 'Preferred course created successfully.');
    }

    public function updatePreferredCourse(Request $request, PreferredCourse $preferredCourse): RedirectResponse
    {
        $validated = $request->validate([
            'cluster_ID' => ['required', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $duplicate = PreferredCourse::query()
            ->where('cluster_ID', $validated['cluster_ID'])
            ->where('name', $validated['name'])
            ->where('course_ID', '!=', $preferredCourse->course_ID)
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'name' => 'This preferred course already exists under the selected cluster.',
            ]);
        }

        $preferredCourse->update($validated);

        return back()->with('success', 'Preferred course updated successfully.');
    }

    public function destroyPreferredCourse(PreferredCourse $preferredCourse): RedirectResponse
    {
        $preferredCourse->delete();

        return back()->with('success', 'Preferred course deleted successfully.');
    }
}
