@extends('users.'.$portal.'.layout')

@section('title', 'Audit Trail')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Audit Trail</h1>
        <p class="mt-1 text-sm text-gray-600">Review important actions and changes across the school. Audit records are read-only.</p>
    </div>
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif
    <form method="GET" action="{{ route($portal.'.audit-trail.index') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach (['search' => 'Search descriptions or references', 'user' => 'User name or ID'] as $field => $label)
                <div><label for="audit-{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label><input id="audit-{{ $field }}" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}" maxlength="200" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            @endforeach
            @foreach (['role' => 'Role', 'module' => 'Module', 'action' => 'Action'] as $field => $label)
                <div><label for="audit-{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label><select id="audit-{{ $field }}" name="{{ $field }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">All {{ strtolower($label) }}s</option>@foreach ($options[$field] as $option)<option value="{{ $option }}" @selected(($filters[$field] ?? '') === $option)>{{ ucfirst($option) }}</option>@endforeach</select></div>
            @endforeach
            <div><label for="audit-status" class="block text-sm font-medium text-gray-700">Status</label><select id="audit-status" name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">All statuses</option>@foreach (['Success', 'Failed', 'Partial'] as $status)<option @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
            @foreach (['from' => 'From date', 'to' => 'To date'] as $field => $label)
                <div><label for="audit-{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label><input type="date" id="audit-{{ $field }}" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            @endforeach
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Apply filters</button>
            <a href="{{ route($portal.'.audit-trail.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Reset</a>
            <label for="audit-per-page" class="text-sm text-gray-600">Rows per page</label>
            <select id="audit-per-page" name="per_page" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach ([10, 25, 50, 100] as $size)<option @selected(($filters['per_page'] ?? 25) == $size)>{{ $size }}</option>@endforeach</select>
        </div>
    </form>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-3 text-sm text-gray-600">{{ number_format($logs->total()) }} record(s) &middot; Times shown in {{ config('audit.timezone') }}</div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr>@foreach (['Date & time', 'User', 'Action / module', 'Reference', 'Description', 'Status'] as $heading)<th scope="col" class="px-5 py-3">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <tr class="align-top hover:bg-gray-50">
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $log->timestamp->setTimezone(config('audit.timezone'))->format('M d, Y') }}<br>{{ $log->timestamp->setTimezone(config('audit.timezone'))->format('h:i:s A') }}<div class="mt-1 text-xs text-gray-400">#{{ $log->audit_id }}</div></td>
                            <td class="px-5 py-4"><div class="font-semibold text-gray-900">{{ $log->user_name }}</div><div class="text-xs text-gray-500">{{ $log->user_id ?? 'No authenticated account' }}</div><div class="mt-1 text-xs capitalize text-gray-600">{{ $log->role }}</div></td>
                            <td class="px-5 py-4"><div class="font-medium text-gray-900">{{ $log->action }}</div><div class="text-xs text-gray-500">{{ $log->module }}</div></td>
                            <td class="max-w-xs break-words px-5 py-4 text-gray-600">@if (strlen($log->reference ?? '') > 150)<details><summary class="cursor-pointer text-[#296374]">View references</summary><div class="mt-2 max-h-48 overflow-auto">{{ $log->reference }}</div></details>@else{{ $log->reference ?: '—' }}@endif</td>
                            <td class="max-w-sm px-5 py-4 text-gray-700">@if (strlen($log->description) > 250)<details><summary class="cursor-pointer text-[#296374]">{{ \Illuminate\Support\Str::limit($log->description, 120) }}</summary><div class="mt-2 max-h-48 overflow-auto">{{ $log->description }}</div></details>@else{{ $log->description }}@endif</td>
                            <td class="px-5 py-4"><span @class(['inline-flex rounded-full px-2 py-1 text-xs font-semibold', 'bg-green-50 text-green-700' => $log->status === 'Success', 'bg-red-50 text-red-700' => $log->status === 'Failed', 'bg-amber-50 text-amber-800' => $log->status === 'Partial'])>{{ $log->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">No audit records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
