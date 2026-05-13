<x-layouts::app :title="__('Application Review')">
    <div class="space-y-4">
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

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Student Applications</h1>
            <form method="GET" class="flex gap-2">
                <select name="status" class="rounded border px-2 py-1 text-sm">
                    <option value="">All</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
                <button class="rounded bg-slate-800 px-3 py-1 text-sm text-white">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto rounded border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-left">
                    <tr>
                        <th class="px-3 py-2">Submitted</th>
                        <th class="px-3 py-2">LRN</th>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Birthdate</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr class="border-t">
                            <td class="px-3 py-2">{{ optional($application->submitted_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-3 py-2">{{ $application->lrn }}</td>
                            <td class="px-3 py-2">{{ $application->first_name }} {{ $application->last_name }}</td>
                            <td class="px-3 py-2">{{ optional($application->birthdate)->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 uppercase">{{ $application->status }}</td>
                            <td class="px-3 py-2">
                                @if ($application->status === 'pending')
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.applications.approve', $application) }}">
                                            @csrf
                                            <button class="rounded bg-emerald-600 px-2.5 py-1 text-xs text-white">Approve</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="flex gap-1">
                                            @csrf
                                            <select name="rejection_reason_id" class="rounded border px-2 py-1 text-xs" required>
                                                <option value="">Reason</option>
                                                @foreach ($rejectionReasons as $reason)
                                                    <option value="{{ $reason->id }}">{{ $reason->reason_name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="rounded bg-red-600 px-2.5 py-1 text-xs text-white">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">Reviewed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-slate-500">No applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $applications->links() }}
    </div>
</x-layouts::app>
