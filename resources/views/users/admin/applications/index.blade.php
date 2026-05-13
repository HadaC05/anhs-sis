@extends('users.admin.layout')

@section('title', 'Application Review')

@section('content')
<div class="space-y-6">
    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6 flex flex-col gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Student Applications</h1>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[220px]">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="LRN or student name" class="rounded border px-3 py-2 text-sm w-full">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" class="rounded border px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Per Page</label>
                <select name="per_page" class="rounded border px-3 py-2 text-sm">
                    @foreach([10, 20, 30, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) ($perPage ?? 20) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded px-4 py-2 text-sm text-white" style="background-color: #296374;">Filter</button>
            @if(request()->hasAny(['search', 'status', 'per_page']))
                <a href="{{ route('admin.applications.index') }}" class="rounded px-4 py-2 text-sm bg-gray-200 text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-100 bg-white/95 backdrop-blur-sm shadow-xl">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3">LRN</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Birthdate</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr class="border-t">
                        <td class="px-4 py-3">{{ optional($application->submitted_at)->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $application->lrn }}</td>
                        <td class="px-4 py-3">{{ $application->first_name }} {{ $application->last_name }}</td>
                        <td class="px-4 py-3">{{ optional($application->birthdate)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 uppercase">{{ $application->status }}</td>
                        <td class="px-4 py-3">
                            @if ($application->status === 'pending')
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.applications.approve', $application) }}">
                                        @csrf
                                        <button class="rounded bg-emerald-600 px-3 py-1 text-xs text-white">Approve</button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="flex gap-1">
                                        @csrf
                                        <select name="rejection_reason_id" class="rounded border px-2 py-1 text-xs" required>
                                            <option value="">Reason</option>
                                            @foreach ($rejectionReasons as $reason)
                                                <option value="{{ $reason->id }}">{{ $reason->reason_name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded bg-red-600 px-3 py-1 text-xs text-white">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-slate-500">Reviewed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No applications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $applications->withQueryString()->links() }}
</div>
@endsection
