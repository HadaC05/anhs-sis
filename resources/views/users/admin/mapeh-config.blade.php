@extends(request()->routeIs('principal.*') ? 'users.principal.layout' : 'users.admin.layout')
@section('title', 'MAPEH Configuration')
@section('content')
@php $prefix = request()->routeIs('principal.*') ? 'principal.' : 'admin.'; @endphp
<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route($prefix.'curriculum-config.index', ['tab' => 'curriculum_subjects']) }}" class="text-sm font-semibold text-[#296374]">&larr; Curriculum subjects</a>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-bold text-[#296374]">MAPEH Configuration</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $curriculum->name }} · {{ $curriculum->gradeLevel?->grade_label }}</p>
        <form method="GET" class="mt-4">
            <label for="mapeh-year" class="mb-1 block text-sm font-semibold">School year</label>
            <select id="mapeh-year" name="SY_ID" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach ($years as $option)<option value="{{ $option->SY_ID }}" @selected($year?->SY_ID === $option->SY_ID)>{{ $option->school_year }}</option>@endforeach
            </select>
        </form>
    </div>
    @if (session('status'))
    <div role="status" id="mapeh-toast" class="fixed right-5 top-5 z-[120] max-w-sm rounded-xl border border-emerald-200 bg-white p-4 text-sm text-emerald-800 shadow-xl">
        <button type="button" onclick="this.parentElement.remove()" class="float-right ml-3" aria-label="Close notification">&times;</button>{{ session('status') }}
    </div>
    <script>setTimeout(() => document.getElementById('mapeh-toast')?.remove(), 7000);</script>
    @endif
    @if ($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
    @if ($configuration)
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="font-bold text-gray-800">{{ $configuration->parentSubject->subject->title }} — {{ $configuration->mode === 'four' ? 'Four components' : 'Paired components' }}</h2>
        <p class="mt-2 text-sm text-gray-600">Each component has equal weight. The combined term grade is available when all component grades have been approved; students see it after every component is released.</p>
        <p class="mt-2 text-sm text-gray-600">Existing standalone MAPEH grades remain on record. For a term with no component entries, its saved MAPEH grade is retained. Component entry replaces that term's combined result once all components are ready.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($configuration->components as $component)
            <div class="rounded-lg border border-gray-200 p-4">
                <p class="font-semibold">{{ \App\Models\MapehConfiguration::labels($configuration->mode)[$component->key] }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $component->curriculumSubject->subject->title }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ 100 / $configuration->components->count() }}% of MAPEH</p>
            </div>
            @endforeach
        </div>
        <p class="mt-4 text-xs text-gray-500">The mapping is fixed for this school year to protect grades and teacher assignments. Select another school year to configure a different arrangement.</p>
    </div>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-5">
            <h2 class="font-bold">Component teachers by class</h2>
            <a href="{{ route($prefix.'teacher-assignments.index') }}" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-semibold text-white">Assign Teachers</a>
        </div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-gray-50"><tr><th class="p-3">Class</th>@foreach ($configuration->components as $component)<th class="p-3">{{ \App\Models\MapehConfiguration::labels($configuration->mode)[$component->key] }}</th>@endforeach</tr></thead>
            <tbody>@forelse ($sections as $section)<tr class="border-t border-gray-100"><td class="p-3 font-semibold">{{ $section->name }}</td>
                @foreach ($configuration->components as $component)
                @php $teacher = $assignments->get($section->section_ID, collect())->firstWhere('curr_subj_ID', $component->curr_subj_ID)?->staff; @endphp
                <td class="p-3 {{ $teacher ? 'text-gray-700' : 'text-amber-700' }}">{{ $teacher ? $teacher->name : 'Not assigned' }}</td>
                @endforeach
            </tr>@empty<tr><td colspan="{{ 1 + $configuration->components->count() }}" class="p-5 text-gray-500">No classes use this curriculum in the selected school year yet.</td></tr>@endforelse</tbody>
        </table></div>
    </div>
    @elseif (! $year || $parents->isEmpty())
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">Add a school year and assign the MAPEH parent subject to this curriculum before configuring its components.</div>
    @else
    <form method="POST" action="{{ route($prefix.'curriculum-config.mapeh.store', $curriculum) }}" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <input type="hidden" name="SY_ID" value="{{ $year->SY_ID }}">
        <div><label for="mapeh-parent" class="mb-1 block text-sm font-semibold">Parent subject</label>
            <select id="mapeh-parent" name="parent_curr_subj_ID" class="w-full rounded-lg border border-gray-300 p-2" required>@foreach ($parents as $parent)<option value="{{ $parent->curr_subj_ID }}" @selected(old('parent_curr_subj_ID') == $parent->curr_subj_ID)>{{ $parent->subject->title }}</option>@endforeach</select>
        </div>
        <div><label for="mapeh-mode" class="mb-1 block text-sm font-semibold">Component arrangement</label>
            <select id="mapeh-mode" name="mode" class="w-full rounded-lg border border-gray-300 p-2">
                <option value="four" @selected(old('mode', 'four') === 'four')>Four — Music, Arts, Physical Education, Health</option>
                <option value="paired" @selected(old('mode') === 'paired')>Paired — Music & Arts, Physical Education & Health</option>
            </select>
        </div>
        <p class="text-sm text-gray-600">Choose the arrangement used by this curriculum. Link existing subjects below, or let the system create the component subjects. Each component can be assigned to a different teacher.</p>
        @foreach (['four', 'paired'] as $mode)
        <div data-mapeh-mode="{{ $mode }}" class="grid gap-4 sm:grid-cols-2">
            @foreach (\App\Models\MapehConfiguration::labels($mode) as $key => $label)
            <div><label for="component-{{ $key }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                <select id="component-{{ $key }}" name="components[{{ $key }}]" class="w-full rounded-lg border border-gray-300 p-2 text-sm">
                    <option value="">Create MAPEH - {{ $label }}</option>
                    @foreach ($subjects as $subject)
                    @if (! $parents->contains('subject_ID', $subject->subject_ID))
                    <option value="{{ $subject->subject_ID }}" @selected(old('components.'.$key) == $subject->subject_ID)>{{ $subject->code }} — {{ $subject->title }}</option>
                    @endif
                    @endforeach
                </select>
            </div>
            @endforeach
        </div>
        @endforeach
        <p class="text-xs text-gray-500">This applies to every class using {{ $curriculum->name }} in {{ $year->school_year }}. The mapping is fixed once saved; other school years keep their own configuration. Existing grades are preserved.</p>
        <button type="submit" class="rounded-lg bg-[#296374] px-5 py-2.5 text-sm font-semibold text-white">Save MAPEH Configuration</button>
    </form>
    <script>
        const mapehMode = document.getElementById('mapeh-mode');
        function updateMapehMode() {
            document.querySelectorAll('[data-mapeh-mode]').forEach(panel => {
                const active = panel.dataset.mapehMode === mapehMode.value;
                panel.classList.toggle('hidden', !active);
                panel.querySelectorAll('select').forEach(select => select.disabled = !active);
            });
        }
        mapehMode.addEventListener('change', updateMapehMode);
        updateMapehMode();
    </script>
    @endif
</div>
@endsection
