@extends(request()->routeIs('principal.*') ? 'users.principal.layout' : 'users.admin.layout')

@section('title', 'School Information')

@section('content')
@php
    $prefix = request()->routeIs('principal.*') ? 'principal.' : 'admin.';
    $logo = $school->logoDataUri();
@endphp
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-800 md:text-3xl">School Information</h1>
    <p class="mt-1 text-sm text-gray-500">Manage the school details shared by both portals and used in school documents.</p>
</div>
@push('toasts')
    <x-password-reset-toasts test-prefix="school-information" />
@endpush
<form method="POST" action="{{ route($prefix.'school-information.update') }}" enctype="multipart/form-data" novalidate class="max-w-4xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    @method('PUT')
    <div class="grid gap-6 sm:grid-cols-2">
        @foreach (['name' => 'School Name', 'school_id' => 'School ID', 'region' => 'Region', 'division' => 'Division', 'district' => 'District'] as $field => $label)
            <div>
                <label for="{{ $field }}" class="mb-2 block text-sm font-semibold text-gray-700">{{ $label }} <span class="text-red-500">*</span></label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $school->$field) }}" required maxlength="{{ $field === 'school_id' ? 6 : ($field === 'name' ? 150 : 100) }}" @if ($field === 'school_id') inputmode="numeric" pattern="[0-9]{6}" @endif class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#296374] focus:ring-[#296374]" @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                @if ($field === 'school_id')<p class="mt-1 text-xs text-gray-500">Enter the six-digit school ID.</p>@endif
                @error($field)<p id="{{ $field }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
    <div class="mt-8 border-t border-gray-200 pt-6">
        <label for="logo" class="mb-2 block text-sm font-semibold text-gray-700">School Logo</label>
        <img id="school-logo-preview" src="{{ $logo ?? '' }}" alt="School logo preview" class="mb-4 h-28 w-28 rounded-lg border border-gray-200 object-contain {{ $logo ? '' : 'hidden' }}">
        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg" class="block w-full text-sm text-gray-600">
        <p class="mt-2 text-xs text-gray-500">PNG or JPG, up to 2 MB and 3000 × 3000 pixels. A new upload replaces the current logo.</p>
        @error('logo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        @if ($school->logo_path)
            <label class="mt-4 flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Remove current logo</label>
        @endif
    </div>
    <div class="mt-8 flex justify-end"><button type="submit" class="rounded-lg bg-[#296374] px-5 py-3 text-sm font-semibold text-white hover:bg-[#20505e]">Save School Information</button></div>
</form>
<script>
    document.getElementById('logo').addEventListener('change', function () {
        const preview = document.getElementById('school-logo-preview');
        const file = this.files[0];
        if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
        const url = file ? URL.createObjectURL(file) : @json($logo);
        preview.src = url || '';
        preview.dataset.objectUrl = file ? url : '';
        preview.classList.toggle('hidden', !url);
    });
</script>
@endsection
