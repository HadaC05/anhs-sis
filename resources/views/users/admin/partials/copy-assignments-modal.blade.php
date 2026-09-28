<dialog id="copyAssignmentsModal" aria-labelledby="copyAssignmentsTitle" class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-lg bg-white p-0 shadow-2xl backdrop:bg-slate-900/70">
    <div class="flex items-center justify-between gap-4 bg-[#296374] px-6 py-4 text-white">
        <h2 id="copyAssignmentsTitle" class="text-xl font-bold">Copy Assignments</h2>
        <button type="button" onclick="document.getElementById('copyAssignmentsModal').close()" aria-label="Close copy assignments" class="rounded-lg px-3 py-1 hover:bg-white/10">&times;</button>
    </div>
    <form id="copyAssignmentsForm" method="POST" action="{{ route($managementRoutePrefix.'teacher-assignments.copy') }}" class="space-y-4 p-6">
        @csrf
        <p class="text-sm text-gray-600">Copy the same subject teachers into another school year. Create or copy destination sections first; section names, grades, clusters and curricula must match.</p>
        @if ($errors->getBag('copyAssignments')->any())
            <div role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->getBag('copyAssignments')->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <div>
            <label for="assignment_copy_grade" class="mb-1 block text-sm font-semibold text-gray-700">Grade level</label>
            <select id="assignment_copy_grade" name="copy_grade_level" required class="{{ $fieldClass }} border-gray-200">
                <option value="all">All grade levels</option>
                @foreach ($gradeLevels as $level)
                    <option value="{{ $level['value'] }}" @selected(old('copy_grade_level') === $level['value'])>{{ $level['label'] }}</option>
                @endforeach
            </select>
        </div>
        @foreach (['source' => 'Source school year', 'target' => 'Destination school year'] as $key => $label)
            <div>
                <label for="assignment_copy_{{ $key }}" class="mb-1 block text-sm font-semibold text-gray-700">{{ $label }}</label>
                <select id="assignment_copy_{{ $key }}" name="{{ $key }}_SY_ID" required class="{{ $fieldClass }} border-gray-200">
                    <option value="">Select school year</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->SY_ID }}" @selected((string) old($key.'_SY_ID') === (string) $year->SY_ID)>{{ $year->school_year }}{{ $year->status ? ' (Active)' : '' }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <p class="text-sm text-gray-500">Existing assignments, unmatched sections and unavailable teachers will be skipped. Student grades and advisory assignments are not copied. The active school year stays the same.</p>
        <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
            <button type="button" onclick="document.getElementById('copyAssignmentsModal').close()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600">Cancel</button>
            <button type="submit" class="rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white">Copy Assignments</button>
        </div>
    </form>
</dialog>
<script>
    const copyAssignmentsDialog = document.getElementById('copyAssignmentsModal');
    document.body.appendChild(copyAssignmentsDialog);
    let copyAssignmentsOverflow;
    function openCopyAssignmentsModal() {
        copyAssignmentsOverflow = document.body.style.overflow;
        copyAssignmentsDialog.showModal();
        document.body.style.overflow = 'hidden';
        document.getElementById('assignment_copy_grade').focus();
    }
    copyAssignmentsDialog.addEventListener('close', () => {
        document.body.style.overflow = copyAssignmentsOverflow;
    });
    copyAssignmentsDialog.addEventListener('click', (event) => {
        const bounds = copyAssignmentsDialog.getBoundingClientRect();
        if (event.target === copyAssignmentsDialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) {
            copyAssignmentsDialog.close();
        }
    });
    const assignmentCopySource = document.getElementById('assignment_copy_source');
    const assignmentCopyTarget = document.getElementById('assignment_copy_target');
    function validateAssignmentCopyYears() {
        assignmentCopyTarget.setCustomValidity(assignmentCopySource.value && assignmentCopySource.value === assignmentCopyTarget.value
            ? 'Choose a destination school year different from the source.' : '');
    }
    assignmentCopySource.addEventListener('change', validateAssignmentCopyYears);
    assignmentCopyTarget.addEventListener('change', validateAssignmentCopyYears);
    validateAssignmentCopyYears();
    document.getElementById('copyAssignmentsForm').addEventListener('submit', function () {
        const submit = this.querySelector('button[type="submit"]');
        submit.disabled = true;
        submit.textContent = 'Copying...';
    });
    @if ($errors->getBag('copyAssignments')->any())
        openCopyAssignmentsModal();
    @endif
</script>
