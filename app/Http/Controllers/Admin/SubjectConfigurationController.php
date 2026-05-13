<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cluster;
use App\Models\PreferredCourse;
use App\Models\Subject;
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
        $clusterId = $request->integer('cluster_ID');
        $preferredCoursesPerPage = (int) $request->integer('preferred_courses_per_page', 10);
        if (! in_array($preferredCoursesPerPage, [5, 10, 15, 25, 50], true)) {
            $preferredCoursesPerPage = 10;
        }

        $preferredSearch = trim($request->string('preferred_courses_search')->toString());
        $preferredClusterId = $request->integer('preferred_courses_cluster_ID');

        $subjects = Subject::query()
            ->with('cluster')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'archived'], true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when(in_array($type, ['core', 'applied', 'specialized'], true), function ($query) use ($type): void {
                $query->where('type', $type);
            })
            ->when($clusterId > 0, function ($query) use ($clusterId): void {
                $query->where('cluster_ID', $clusterId);
            })
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        $preferredCourses = PreferredCourse::query()
            ->with('cluster')
            ->when($preferredSearch !== '', function ($query) use ($preferredSearch): void {
                $query->where(function ($inner) use ($preferredSearch): void {
                    $inner->where('name', 'like', "%{$preferredSearch}%")
                        ->orWhere('description', 'like', "%{$preferredSearch}%");
                });
            })
            ->when($preferredClusterId > 0, function ($query) use ($preferredClusterId): void {
                $query->where('cluster_ID', $preferredClusterId);
            })
            ->orderBy('name')
            ->paginate($preferredCoursesPerPage, ['*'], 'preferred_courses_page')
            ->withQueryString();

        $clusters = Cluster::query()
            ->orderBy('name')
            ->get(['cluster_ID', 'name']);

        $preferredClusters = Cluster::query()
            ->whereIn('name', [
                'Arts, Social Sciences, And Humanities',
                'Business And Entrepreneurship',
                'Science, Technology, Engineering, and Mathematics',
            ])
            ->orderBy('name')
            ->get(['cluster_ID', 'name']);

        return view('users.admin.subject-config', [
            'subjects' => $subjects,
            'preferredCourses' => $preferredCourses,
            'clusters' => $clusters,
            'preferredClusters' => $preferredClusters,
            'perPage' => $perPage,
            'preferredCoursesPerPage' => $preferredCoursesPerPage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cluster_ID' => ['required', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'code' => ['required', 'string', 'max:255', 'unique:subjects,code'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['core', 'applied', 'specialized'])],
        ]);

        Subject::query()->create($validated);

        return back()->with('success', 'Subject created successfully.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'cluster_ID' => ['required', 'integer', Rule::exists('clusters', 'cluster_ID')],
            'code' => ['required', 'string', 'max:255', Rule::unique('subjects', 'code')->ignore($subject->subject_ID, 'subject_ID')],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['core', 'applied', 'specialized'])],
        ]);

        $subject->update($validated);

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
