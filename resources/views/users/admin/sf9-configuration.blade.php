@extends(request()->routeIs('principal.*') ? 'users.principal.layout' : 'users.admin.layout')

@section('title', 'School Forms')

@section('content')
@php $prefix = request()->routeIs('principal.*') ? 'principal.' : 'admin.'; @endphp
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-800 md:text-3xl">School Forms</h1>
    <p class="mt-2 text-sm text-gray-500">Configure report cards and attendance forms. These settings are shared by the Admin and Principal portals.</p>
</div>
@if(session('status'))<div role="status" class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
<nav aria-label="School form configuration" class="mb-6 flex max-w-5xl gap-2 border-b border-gray-200">
    @foreach(['sf9' => 'SF9 ? Report Cards', 'sf2' => 'SF2 ? Attendance'] as $key => $label)
        <a href="{{ route($prefix.'school-forms.edit', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif class="border-b-2 px-4 py-3 text-sm font-semibold {{ $tab === $key ? 'border-[#296374] text-[#296374]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">{{ $label }}</a>
    @endforeach
</nav>
@if($tab === 'sf9')
<form method="POST" action="{{ route($prefix.'sf9-configuration.update') }}" class="max-w-5xl space-y-6">
    @csrf
    @method('PUT')
    @foreach(['junior_high' => 'Junior High School', 'senior_high' => 'Senior High School'] as $level => $label)
        <fieldset class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <legend class="px-2 text-lg font-bold text-gray-800">{{ $label }}</legend>
            <div class="space-y-4">
                @foreach($formats[$level] as $key => $name)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-4">
                        <label class="flex items-center gap-3 text-sm font-semibold text-gray-800">
                            <input type="radio" name="{{ $level }}" value="{{ $key }}" @checked(old($level, $configuration->$level) === $key) required>
                            {{ $name }}
                        </label>
                        <a href="{{ route($prefix.'sf9-configuration.preview', $key) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-[#296374] hover:underline">Preview <span class="sr-only">{{ $name }} (opens a new tab)</span></a>
                    </div>
                @endforeach
            </div>
            @error($level)<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            <p class="mt-4 text-sm text-gray-500">{{ $level === 'junior_high' ? 'The updated format follows the supplied SY 2026–2027 form. Grading columns and MAPEH rows follow your existing academic configuration so recorded grades remain visible.' : 'The current Senior High format is retained. Additional Senior High formats can be added when a new template is available.' }}</p>
        </fieldset>
    @endforeach
    <p class="text-sm text-gray-500">Selections apply to individual and bulk SF9 generation, including reprints for previous school years. Existing grades and downloaded reports are unchanged. You can switch back to the original format at any time.</p>
    <div class="flex justify-end"><button type="submit" class="rounded-lg bg-[#296374] px-5 py-3 text-sm font-semibold text-white hover:bg-[#20505e]">Save SF9 Formats</button></div>
</form>
@else
<form method="POST" action="{{ route($prefix.'school-forms.sf2.update') }}" class="max-w-5xl space-y-6">
    @csrf
    @method('PUT')
    <fieldset class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <legend class="px-2 text-lg font-bold text-gray-800">SF2 Attendance Format</legend>
        <p class="mb-5 text-sm text-gray-500">Choose the form teachers will upload and use in the detailed monthly attendance view.</p>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach($sf2Formats as $key => $format)
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-5 has-[:checked]:border-[#296374] has-[:checked]:bg-[#eef5f7]">
                    <input class="mt-1" type="radio" name="format" value="{{ $key }}" @checked(old('format', $sf2Configuration->format) === $key) required>
                    <span><span class="block text-base font-bold text-gray-800">{{ $format['label'] }}</span><span class="mt-2 block text-sm text-gray-600">{{ $format['description'] }}</span><span class="mt-4 block rounded border border-gray-200 bg-white p-3 text-center text-xs font-semibold text-gray-600">Learner ? Daily marks ? Absent ? {{ $key === 'lis' ? 'Present' : 'Tardy' }}</span></span>
                </label>
            @endforeach
        </div>
        @error('format')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
    </fieldset>
    <p class="text-sm text-gray-500">This selection applies school-wide. Previously uploaded records appear using the selected columns. Switching forms preserves saved attendance, source files, and SF9 totals.</p>
    <div class="flex justify-end"><button type="submit" class="rounded-lg bg-[#296374] px-5 py-3 text-sm font-semibold text-white hover:bg-[#20505e]">Save SF2 Format</button></div>
</form>
@endif
@endsection
