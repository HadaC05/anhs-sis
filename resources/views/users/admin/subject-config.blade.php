@extends(request()->routeIs('principal.*') ? 'users.principal.layout' : 'users.admin.layout')

@php
    $managementRoutePrefix = request()->routeIs('principal.*') ? 'principal.' : 'admin.';
@endphp

@section('title', 'Tracks, Clusters & Subjects')

@push('toasts')
    <x-password-reset-toasts :include-errors="false" test-prefix="subject-config" />
@endpush

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $subjectModalOpen = old('_form') === 'subject' && $errors->any();
    $trackModalOpen = old('_form') === 'track' && $errors->any();
    $clusterModalOpen = old('_form') === 'cluster' && $errors->any();
    $requestedTab = request('tab', 'tracks');
    $activeTab = $subjectModalOpen ? 'subjects'
        : ($trackModalOpen ? 'tracks'
        : ($clusterModalOpen ? 'clusters'
        : (in_array($requestedTab, ['tracks', 'clusters', 'subjects'], true) ? $requestedTab : 'tracks')));
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-800">Tracks, Clusters &amp; Subjects</h1>
        <p class="mt-1 text-sm text-gray-500">View the Senior High structure and manage the subject masterfile.</p>
    </div>
    <div>
        <button type="button" id="addTrackHeaderBtn" onclick="openTrackModal()"
            class="{{ $activeTab === 'tracks' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">Add Track</button>
        <button type="button" id="addClusterHeaderBtn" onclick="openClusterModal()"
            class="{{ $activeTab === 'clusters' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">Add Cluster</button>
        <button type="button" id="addSubjectHeaderBtn" onclick="openSubjectModal()"
            class="{{ $activeTab === 'subjects' ? 'inline-flex' : 'hidden' }} items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90" style="background-color: #296374;">Add Subject</button>
    </div>
</div>

@if ($errors->any() && ! in_array(old('_form'), ['track', 'cluster', 'subject'], true))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
            <span class="text-sm font-bold">T</span>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500">Tracks</p>
            <p class="text-xl font-bold leading-tight text-gray-800">{{ number_format($trackCount) }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
            <span class="text-sm font-bold">C</span>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500">Clusters</p>
            <p class="text-xl font-bold leading-tight text-gray-800">{{ number_format($clusterCount) }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
            <span class="text-sm font-bold">S</span>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500">Subjects</p>
            <p class="text-xl font-bold leading-tight text-gray-800">{{ number_format($totalSubjects) }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
            <span class="text-sm font-bold">A</span>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500">Active subjects</p>
            <p class="text-xl font-bold leading-tight text-gray-800">{{ number_format($activeSubjectCount) }}</p>
        </div>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-3 pt-1">
        <nav class="flex gap-1" role="tablist" aria-label="Tracks, clusters, and subjects tabs">
            <button type="button" id="tracksTabBtn" onclick="switchSubjectConfigTab('tracks')" role="tab"
                aria-selected="{{ $activeTab === 'tracks' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-semibold transition {{ $activeTab === 'tracks' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Tracks
                <span id="tracksTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'tracks' ? '' : 'hidden' }}"></span>
            </button>
            <button type="button" id="clustersTabBtn" onclick="switchSubjectConfigTab('clusters')" role="tab"
                aria-selected="{{ $activeTab === 'clusters' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-semibold transition {{ $activeTab === 'clusters' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Clusters
                <span id="clustersTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'clusters' ? '' : 'hidden' }}"></span>
            </button>
            <button type="button" id="subjectsTabBtn" onclick="switchSubjectConfigTab('subjects')" role="tab"
                aria-selected="{{ $activeTab === 'subjects' ? 'true' : 'false' }}"
                class="relative px-4 py-3 text-sm font-semibold transition {{ $activeTab === 'subjects' ? 'text-[#296374]' : 'text-gray-500 hover:text-gray-700' }}">
                Subjects
                <span id="subjectsTabIndicator" class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-[#296374] {{ $activeTab === 'subjects' ? '' : 'hidden' }}"></span>
            </button>
        </nav>
    </div>

    <div id="tracksTabPanel" class="{{ $activeTab === 'tracks' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 bg-slate-50/70 px-4 py-3">
            <form method="GET" action="{{ route($managementRoutePrefix.'subject-config.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="tracks">
                <div class="relative min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path></svg>
                    <input type="search" name="track_search" value="{{ request('track_search') }}" placeholder="Search tracks" class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-sm outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <button type="submit" class="h-9 rounded-lg bg-[#296374] px-4 text-sm font-semibold text-white">Search</button>
                @if (request('track_search'))<a href="{{ route($managementRoutePrefix.'subject-config.index', ['tab' => 'tracks']) }}" class="h-9 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[520px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                    <tr><th class="px-5 py-3">Track</th><th class="px-5 py-3">Clusters</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($tracks as $track)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 font-semibold text-gray-800">{{ $track->name }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ number_format($track->clusters_count) }}</td>
                            <td class="px-5 py-3.5 text-right"><button type="button" onclick='openTrackModal(@json(['track_ID' => $track->track_ID, 'name' => $track->name]))' class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit track" aria-label="Edit {{ $track->name }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500">No tracks match your search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="clustersTabPanel" class="{{ $activeTab === 'clusters' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 bg-slate-50/70 px-4 py-3">
            <form method="GET" action="{{ route($managementRoutePrefix.'subject-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="clusters">
                <div class="relative min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path></svg>
                    <input type="search" name="cluster_search" value="{{ request('cluster_search') }}" placeholder="Search clusters" class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-9 pr-3 text-sm outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="cluster_track_ID" class="h-9 max-w-64 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All tracks</option>
                    @foreach ($allTracks as $track)<option value="{{ $track->track_ID }}" @selected((int) request('cluster_track_ID') === (int) $track->track_ID)>{{ $track->name }}</option>@endforeach
                </select>
                <button type="submit" class="h-9 rounded-lg bg-[#296374] px-4 text-sm font-semibold text-white">Apply</button>
                @if (request()->hasAny(['cluster_search', 'cluster_track_ID']))<a href="{{ route($managementRoutePrefix.'subject-config.index', ['tab' => 'clusters']) }}" class="h-9 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                    <tr><th class="px-5 py-3">Cluster</th><th class="px-5 py-3">Track</th><th class="px-5 py-3">Subjects</th><th class="px-5 py-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($clusters as $cluster)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 font-semibold text-gray-800">{{ $cluster->name }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $cluster->track?->name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ number_format($cluster->subjects_count) }}</td>
                            <td class="px-5 py-3.5 text-right"><button type="button" onclick='openClusterModal(@json(['cluster_ID' => $cluster->cluster_ID, 'track_ID' => $cluster->track_ID, 'name' => $cluster->name]))' class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit cluster" aria-label="Edit {{ $cluster->name }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No clusters match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="subjectsTabPanel" class="{{ $activeTab === 'subjects' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route($managementRoutePrefix.'subject-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="subjects">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                    </svg>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search code or title"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="type" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All types</option>
                    @foreach ($subjectTypes as $subjectType)
                        <option value="{{ $subjectType->key }}" @selected(request('type') === $subjectType->key)>{{ $subjectType->label }}</option>
                    @endforeach
                </select>
                <select name="school_level" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All school levels</option>
                    <option value="Junior High School" @selected(request('school_level') === 'Junior High School')>Junior High School</option>
                    <option value="Senior High School" @selected(request('school_level') === 'Senior High School')>Senior High School</option>
                </select>
                <select name="cluster_ID" class="h-10 max-w-56 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All clusters</option>
                    @foreach ($subjectClusters as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" @selected((int) request('cluster_ID') === (int) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                </select>
                <select name="per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) ($perPage ?? 15) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['search', 'type', 'school_level', 'cluster_ID', 'status', 'per_page']))
                    <a href="{{ route($managementRoutePrefix.'subject-config.index', ['tab' => 'subjects']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Code</th>
                        <th class="border-r border-gray-200 px-5 py-4">Title</th>
                        <th class="border-r border-gray-200 px-5 py-4">Type</th>
                        <th class="border-r border-gray-200 px-5 py-4">School Level</th>
                        <th class="border-r border-gray-200 px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($subjects as $subject)
                        @php
                            $subjectPayload = [
                                'subject_ID' => $subject->subject_ID,
                                'code' => $subject->code,
                                'title' => $subject->title,
                                'type' => $subject->type,
                                'school_level' => $subject->school_level,
                                'cluster_ID' => $subject->cluster_ID,
                            ];
                            $typeBadge = match ($subject->type) {
                                'general' => 'bg-slate-100 text-slate-700 ring-slate-200',
                                'core' => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
                                'elective' => 'bg-violet-50 text-violet-700 ring-violet-200',
                                default => 'bg-gray-100 text-gray-700 ring-gray-200',
                            };
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $subject->code }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $subject->title }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide ring-1 {{ $typeBadge }}">
                                    {{ $subject->type }}
                                </span>
                            </td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $subject->school_level }}</td>
                            <td class="border-r border-gray-100 px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $subject->status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                    {{ ucfirst($subject->status ?? 'active') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick='openSubjectModal(@json($subjectPayload))'
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route($managementRoutePrefix.'subject-config.delete', $subject) }}" method="POST" class="inline" data-confirm-action="{{ $subject->status === 'active' ? 'Archive' : 'Restore' }}" data-confirm-message="{{ $subject->status === 'active' ? 'Archive this subject?' : 'Restore this subject?' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-2 text-gray-500 transition {{ $subject->status === 'active' ? 'hover:bg-amber-50 hover:text-amber-700' : 'hover:bg-emerald-50 hover:text-emerald-600' }}" title="{{ $subject->status === 'active' ? 'Archive' : 'Restore' }}">
                                            @if ($subject->status === 'active')
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                                </svg>
                                            @else
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-gray-500">No subjects yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subjects->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $subjects->appends(['tab' => 'subjects'])->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- Preferred Courses are retained in storage for historical records but are no longer part of this page.
    <div id="preferredCoursesTabPanel" class="{{ $activeTab === 'preferred_courses' ? '' : 'hidden' }}">
        <div class="border-b border-gray-100 px-4 py-4">
            <form method="GET" action="{{ route($managementRoutePrefix.'subject-config.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="preferred_courses">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="type" value="{{ request('type') }}">
                <input type="hidden" name="school_level" value="{{ request('school_level') }}">
                <input type="hidden" name="cluster_ID" value="{{ request('cluster_ID') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', $perPage ?? 15) }}">

                <div class="relative min-w-[200px] flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                    </svg>
                    <input type="search" name="preferred_courses_search" value="{{ request('preferred_courses_search') }}" placeholder="Search course name"
                        class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 text-sm outline-none transition focus:border-[#296374] focus:bg-white focus:ring-2 focus:ring-[#296374]/10">
                </div>
                <select name="preferred_courses_cluster_ID" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    <option value="">All clusters</option>
                    @foreach ($preferredClusters as $cluster)
                        <option value="{{ $cluster->cluster_ID }}" @selected((int) request('preferred_courses_cluster_ID') === (int) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                    @endforeach
                </select>
                <select name="preferred_courses_per_page" class="h-10 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                    @foreach ([5, 10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" {{ (int) ($preferredCoursesPerPage ?? 10) === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-bold text-white shadow-sm" style="background-color: #296374;">Apply</button>
                @if (request()->hasAny(['preferred_courses_search', 'preferred_courses_cluster_ID', 'preferred_courses_per_page']))
                    <a href="{{ route($managementRoutePrefix.'subject-config.index', ['tab' => 'preferred_courses']) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-300 bg-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        <th class="border-r border-gray-200 px-5 py-4">Course</th>
                        <th class="border-r border-gray-200 px-5 py-4">Academic Cluster</th>
                        <th class="border-r border-gray-200 px-5 py-4">Description</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    @forelse ($preferredCourses as $course)
                        @php
                            $coursePayload = [
                                'course_ID' => $course->course_ID,
                                'cluster_ID' => $course->cluster_ID,
                                'name' => $course->name,
                                'description' => $course->description,
                            ];
                        @endphp
                        <tr class="bg-white transition even:bg-gray-50/70 hover:bg-[#296374]/[0.06]">
                            <td class="border-r border-gray-100 px-5 py-4 font-semibold text-gray-900">{{ $course->name }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ optional($course->cluster)->name ?? '—' }}</td>
                            <td class="border-r border-gray-100 px-5 py-4 text-gray-700">{{ $course->description ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" onclick='openPreferredCourseModal(@json($coursePayload))'
                                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-[#296374]" title="Edit">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <form action="{{ route($managementRoutePrefix.'subject-config.preferred-courses.delete', $course) }}" method="POST" class="inline" onsubmit="return confirm('Delete this preferred course?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600" title="Delete">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center text-gray-500">No preferred courses yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($preferredCourses->hasPages())
            <div class="border-t border-gray-100 bg-gray-50 px-4 py-3">
                {{ $preferredCourses->appends(['tab' => 'preferred_courses'])->withQueryString()->links() }}
            </div>
        @endif
    </div>
    --}}
</div>

<div id="trackModal" role="dialog" aria-modal="true" aria-labelledby="trackModalTitle" data-open="{{ $trackModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[200] {{ $trackModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4">
    <div class="w-full max-w-md overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between bg-[#296374] px-5 py-4">
            <h3 id="trackModalTitle" class="text-lg font-semibold text-white">Add Track</h3>
            <button type="button" onclick="closeTrackModal()" class="rounded-lg p-2 text-white/80 hover:bg-white/10 hover:text-white" aria-label="Close"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <form id="trackForm" action="{{ route($managementRoutePrefix.'subject-config.tracks.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="track">
            <input type="hidden" id="track_method" name="_method" value="POST">
            <div class="px-5 py-5">
                <label for="track_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">Track Name <span class="text-red-500">*</span></label>
                <input id="track_name" name="name" value="{{ old('_form') === 'track' ? old('name') : '' }}" required class="{{ $fieldClass }} {{ old('_form') === 'track' && $errors->has('name') ? 'border-red-300' : 'border-gray-200' }}" placeholder="e.g. Academic Track">
                @if (old('_form') === 'track') @error('name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror @endif
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3">
                <button type="button" onclick="closeTrackModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">Cancel</button>
                <button id="trackSubmit" type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Save Track</button>
            </div>
        </form>
    </div>
</div>

<div id="clusterModal" role="dialog" aria-modal="true" aria-labelledby="clusterModalTitle" data-open="{{ $clusterModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[200] {{ $clusterModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4">
    <div class="w-full max-w-md overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
        <div class="flex items-center justify-between bg-[#296374] px-5 py-4">
            <h3 id="clusterModalTitle" class="text-lg font-semibold text-white">Add Cluster</h3>
            <button type="button" onclick="closeClusterModal()" class="rounded-lg p-2 text-white/80 hover:bg-white/10 hover:text-white" aria-label="Close"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
        </div>
        <form id="clusterForm" action="{{ route($managementRoutePrefix.'subject-config.clusters.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="cluster">
            <input type="hidden" id="cluster_method" name="_method" value="POST">
            <div class="space-y-4 px-5 py-5">
                <div>
                    <label for="cluster_track_id" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">Track <span class="text-red-500">*</span></label>
                    <select id="cluster_track_id" name="track_ID" required class="{{ $fieldClass }} {{ old('_form') === 'cluster' && $errors->has('track_ID') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select track</option>
                        @foreach ($allTracks as $track)<option value="{{ $track->track_ID }}" @selected(old('_form') === 'cluster' && (string) old('track_ID') === (string) $track->track_ID)>{{ $track->name }}</option>@endforeach
                    </select>
                    @if (old('_form') === 'cluster') @error('track_ID')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror @endif
                </div>
                <div>
                    <label for="cluster_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">Cluster Name <span class="text-red-500">*</span></label>
                    <input id="cluster_name" name="name" value="{{ old('_form') === 'cluster' ? old('name') : '' }}" required class="{{ $fieldClass }} {{ old('_form') === 'cluster' && $errors->has('name') ? 'border-red-300' : 'border-gray-200' }}" placeholder="Enter cluster name">
                    @if (old('_form') === 'cluster') @error('name')<p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>@enderror @endif
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3">
                <button type="button" onclick="closeClusterModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">Cancel</button>
                <button id="clusterSubmit" type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Save Cluster</button>
            </div>
        </form>
    </div>
</div>

<div id="subjectModal" role="dialog" aria-modal="true" aria-labelledby="subjectModalTitle" data-open="{{ $subjectModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[200] {{ $subjectModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="subjectModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Subject</h3>
                </div>
                <button type="button" onclick="closeSubjectModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="subjectForm" action="{{ route($managementRoutePrefix.'subject-config.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="subject">
            <input type="hidden" id="subject_method" name="_method" value="POST">

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="subject_school_level" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">School Level <span class="text-red-500">*</span></label>
                    <select id="subject_school_level" name="school_level" required
                        class="{{ $fieldClass }} {{ $errors->has('school_level') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select school level</option>
                        <option value="Junior High School" @selected(old('school_level') === 'Junior High School')>Junior High School</option>
                        <option value="Senior High School" @selected(old('school_level') === 'Senior High School')>Senior High School</option>
                    </select>
                    @error('school_level')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="subject_type" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Subject Type <span class="text-red-500">*</span></label>
                    <select id="subject_type" name="type" required
                        class="{{ $fieldClass }} {{ $errors->has('type') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select subject type</option>
                        @foreach ($subjectTypes as $subjectType)
                            <option value="{{ $subjectType->key }}" @selected(old('type') === $subjectType->key)>{{ $subjectType->label }}</option>
                        @endforeach
                    </select>
                    <p id="subject_type_hint" class="mt-1 hidden text-xs text-gray-500">Junior High subjects are automatically classified as General.</p>
                    @error('type')
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div id="subject_cluster_field" class="hidden">
                    <label for="subject_cluster_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Cluster <span class="text-red-500">*</span></label>
                    <select id="subject_cluster_id" name="cluster_ID"
                        class="{{ $fieldClass }} {{ old('_form') === 'subject' && $errors->has('cluster_ID') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select cluster</option>
                        @foreach ($subjectClusters as $cluster)
                            <option value="{{ $cluster->cluster_ID }}" @selected((string) old('cluster_ID') === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                    @error('cluster_ID')
                        @if (old('_form') === 'subject')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="subject_code" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Code <span class="text-red-500">*</span></label>
                        <input id="subject_code" name="code" type="text" value="{{ old('code') }}" required
                            class="{{ $fieldClass }} {{ $errors->has('code') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('code')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="subject_title" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Name <span class="text-red-500">*</span></label>
                        <input id="subject_title" name="title" type="text" value="{{ old('title') }}" required
                            class="{{ $fieldClass }} {{ $errors->has('title') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('title')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closeSubjectModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="subjectSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Subject
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Preferred-course management is intentionally no longer exposed in the interface.
<div id="preferredCourseModal" role="dialog" aria-modal="true" aria-labelledby="preferredCourseModalTitle" data-open="{{ $preferredCourseModalOpen ? 'true' : 'false' }}"
    class="fixed inset-0 z-[200] {{ $preferredCourseModalOpen ? 'flex' : 'hidden' }} items-center justify-center bg-slate-900/70 p-4">
    <div class="mx-auto w-full max-w-xl overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">School management</p>
                    <h3 id="preferredCourseModalTitle" class="mt-1 text-xl font-bold tracking-tight text-white">Add Preferred Course</h3>
                </div>
                <button type="button" onclick="closePreferredCourseModal()" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-white/20 text-white transition hover:bg-white/10" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <form id="preferredCourseForm" action="{{ route($managementRoutePrefix.'subject-config.preferred-courses.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="preferred_course">
            <input type="hidden" id="preferred_course_method" name="_method" value="POST">

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="preferred_course_cluster_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Academic Cluster <span class="text-red-500">*</span></label>
                    <select id="preferred_course_cluster_id" name="cluster_ID" required
                        class="{{ $fieldClass }} {{ $errors->has('cluster_ID') ? 'border-red-300' : 'border-gray-200' }}">
                        <option value="">Select cluster</option>
                        @foreach ($preferredClusters as $cluster)
                            <option value="{{ $cluster->cluster_ID }}" @selected((string) old('cluster_ID') === (string) $cluster->cluster_ID)>{{ $cluster->name }}</option>
                        @endforeach
                    </select>
                    @error('cluster_ID')
                        @if (old('_form') === 'preferred_course')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="preferred_course_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Course Name <span class="text-red-500">*</span></label>
                    <input id="preferred_course_name" name="name" type="text" value="{{ old('name') }}" required
                        class="{{ $fieldClass }} {{ $errors->has('name') ? 'border-red-300' : 'border-gray-200' }}">
                    @error('name')
                        @if (old('_form') === 'preferred_course')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
                <div>
                    <label for="preferred_course_description" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Description</label>
                    <textarea id="preferred_course_description" name="description" rows="3"
                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15 {{ $errors->has('description') ? 'border-red-300' : 'border-gray-200' }}">{{ old('description') }}</textarea>
                    @error('description')
                        @if (old('_form') === 'preferred_course')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @endif
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" onclick="closePreferredCourseModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="preferredCourseSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">
                    Save Preferred Course
                </button>
            </div>
        </form>
    </div>
</div>
--}}

<script>
    const trackModalElement = document.getElementById('trackModal');
    const clusterModalElement = document.getElementById('clusterModal');
    const subjectModalElement = document.getElementById('subjectModal');

    // Move overlays out of the layout's main stacking context so they sit above
    // both the fixed top bar and sidebar.
    document.body.append(trackModalElement, clusterModalElement, subjectModalElement);

    function syncSubjectFields() {
        const schoolLevel = document.getElementById('subject_school_level').value;
        const type = document.getElementById('subject_type');
        const typeHint = document.getElementById('subject_type_hint');
        const clusterField = document.getElementById('subject_cluster_field');
        const cluster = document.getElementById('subject_cluster_id');
        const isJuniorHigh = schoolLevel === 'Junior High School';
        const isSeniorHigh = schoolLevel === 'Senior High School';

        Array.from(type.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            option.hidden = isJuniorHigh ? option.value !== 'general' : option.value === 'general';
            option.disabled = option.hidden;
        });

        if (isJuniorHigh) {
            type.value = 'general';
        } else if (type.value === 'general') {
            type.value = '';
        }

        type.disabled = !schoolLevel || isJuniorHigh;
        typeHint.classList.toggle('hidden', !isJuniorHigh);

        const showCluster = isSeniorHigh && type.value === 'elective';
        clusterField.classList.toggle('hidden', !showCluster);
        cluster.required = showCluster;
        cluster.disabled = !showCluster;

        if (!showCluster) {
            cluster.value = '';
        }
    }

    function switchSubjectConfigTab(tab) {
        const tabs = ['tracks', 'clusters', 'subjects'];
        const addTrackBtn = document.getElementById('addTrackHeaderBtn');
        const addClusterBtn = document.getElementById('addClusterHeaderBtn');
        const addSubjectBtn = document.getElementById('addSubjectHeaderBtn');

        tabs.forEach((tabName) => {
            const isActive = tabName === tab;
            const button = document.getElementById(`${tabName}TabBtn`);
            const panel = document.getElementById(`${tabName}TabPanel`);
            const indicator = document.getElementById(`${tabName}TabIndicator`);

            panel.classList.toggle('hidden', !isActive);
            button.classList.toggle('text-[#296374]', isActive);
            button.classList.toggle('text-gray-500', !isActive);
            button.classList.toggle('hover:text-gray-700', !isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            indicator.classList.toggle('hidden', !isActive);
        });

        [[addTrackBtn, 'tracks'], [addClusterBtn, 'clusters'], [addSubjectBtn, 'subjects']].forEach(([button, tabName]) => {
            const show = tab === tabName;
            button.classList.toggle('hidden', !show);
            button.classList.toggle('inline-flex', show);
        });

        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    function setModalVisibility(modal, open) {
        modal.classList.toggle('hidden', !open);
        modal.classList.toggle('flex', open);
        modal.setAttribute('data-open', open ? 'true' : 'false');
    }

    function openTrackModal(track = null) {
        const form = document.getElementById('trackForm');
        document.getElementById('trackModalTitle').textContent = track ? 'Edit Track' : 'Add Track';
        document.getElementById('trackSubmit').textContent = track ? 'Update Track' : 'Save Track';
        document.getElementById('track_method').value = track ? 'PUT' : 'POST';
        form.action = track
            ? '{{ route($managementRoutePrefix.'subject-config.tracks.update', ['track' => '__TRACK__']) }}'.replace('__TRACK__', track.track_ID)
            : '{{ route($managementRoutePrefix.'subject-config.tracks.store') }}';
        document.getElementById('track_name').value = track?.name || '';
        setModalVisibility(trackModalElement, true);
        document.getElementById('track_name').focus();
    }

    function closeTrackModal() {
        setModalVisibility(trackModalElement, false);
    }

    function openClusterModal(cluster = null) {
        const form = document.getElementById('clusterForm');
        document.getElementById('clusterModalTitle').textContent = cluster ? 'Edit Cluster' : 'Add Cluster';
        document.getElementById('clusterSubmit').textContent = cluster ? 'Update Cluster' : 'Save Cluster';
        document.getElementById('cluster_method').value = cluster ? 'PUT' : 'POST';
        form.action = cluster
            ? '{{ route($managementRoutePrefix.'subject-config.clusters.update', ['cluster' => '__CLUSTER__']) }}'.replace('__CLUSTER__', cluster.cluster_ID)
            : '{{ route($managementRoutePrefix.'subject-config.clusters.store') }}';
        document.getElementById('cluster_track_id').value = cluster?.track_ID || '';
        document.getElementById('cluster_name').value = cluster?.name || '';
        setModalVisibility(clusterModalElement, true);
        document.getElementById('cluster_track_id').focus();
    }

    function closeClusterModal() {
        setModalVisibility(clusterModalElement, false);
    }

    function openSubjectModal(subject = null) {
        const form = document.getElementById('subjectForm');
        const method = document.getElementById('subject_method');
        const title = document.getElementById('subjectModalTitle');
        const submit = document.getElementById('subjectSubmit');
        const modal = document.getElementById('subjectModal');
        const updateRouteTemplate = '{{ route($managementRoutePrefix.'subject-config.update', ['subject' => '__SUBJECT__']) }}';

        if (subject) {
            title.textContent = 'Edit Subject';
            submit.textContent = 'Update Subject';
            form.action = updateRouteTemplate.replace('__SUBJECT__', subject.subject_ID);
            method.value = 'PUT';
            document.getElementById('subject_code').value = subject.code || '';
            document.getElementById('subject_title').value = subject.title || '';
            document.getElementById('subject_type').value = subject.type || '';
            document.getElementById('subject_school_level').value = subject.school_level || '';
            document.getElementById('subject_cluster_id').value = subject.cluster_ID || '';
        } else {
            title.textContent = 'Add Subject';
            submit.textContent = 'Save Subject';
            form.action = '{{ route($managementRoutePrefix.'subject-config.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        syncSubjectFields();

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('subject_school_level').focus();
    }

    function closeSubjectModal() {
        const modal = document.getElementById('subjectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }

    /*
    function openPreferredCourseModal(course = null) {
        const form = document.getElementById('preferredCourseForm');
        const method = document.getElementById('preferred_course_method');
        const title = document.getElementById('preferredCourseModalTitle');
        const submit = document.getElementById('preferredCourseSubmit');
        const modal = document.getElementById('preferredCourseModal');
        const updateRouteTemplate = '{{ route($managementRoutePrefix.'subject-config.preferred-courses.update', ['preferredCourse' => '__COURSE__']) }}';

        if (course) {
            title.textContent = 'Edit Preferred Course';
            submit.textContent = 'Update Preferred Course';
            form.action = updateRouteTemplate.replace('__COURSE__', course.course_ID);
            method.value = 'PUT';
            document.getElementById('preferred_course_cluster_id').value = course.cluster_ID || '';
            document.getElementById('preferred_course_name').value = course.name || '';
            document.getElementById('preferred_course_description').value = course.description || '';
        } else {
            title.textContent = 'Add Preferred Course';
            submit.textContent = 'Save Preferred Course';
            form.action = '{{ route($managementRoutePrefix.'subject-config.preferred-courses.store') }}';
            method.value = 'POST';
            form.reset();
            method.value = 'POST';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('data-open', 'true');
        document.getElementById('preferred_course_cluster_id').focus();
    }

    function closePreferredCourseModal() {
        const modal = document.getElementById('preferredCourseModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('data-open', 'false');
    }
    */

    document.getElementById('subject_school_level').addEventListener('change', syncSubjectFields);
    document.getElementById('subject_type').addEventListener('change', syncSubjectFields);
    syncSubjectFields();

    document.getElementById('subjectModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeSubjectModal();
        }
    });

    trackModalElement.addEventListener('click', function (e) {
        if (e.target === this) closeTrackModal();
    });

    clusterModalElement.addEventListener('click', function (e) {
        if (e.target === this) closeClusterModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }

        if (document.getElementById('subjectModal').getAttribute('data-open') === 'true') {
            closeSubjectModal();
        }
        if (trackModalElement.getAttribute('data-open') === 'true') closeTrackModal();
        if (clusterModalElement.getAttribute('data-open') === 'true') closeClusterModal();
    });
</script>
@endsection
