@php
    $confirmContext = $confirmContext ?? 'bulk';
    $studentDisplayName = $studentDisplayName ?? 'this student';
    $enrollmentResult = session('enrollment_result');
    $statusLabels = \App\Models\EnrollmentStatus::options();
    $activeStatusSlugs = \App\Models\EnrollmentStatus::activeSlugs();
@endphp

@push('modals')
<div id="enrollmentConfirmModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4 pt-24" role="dialog" aria-modal="true" aria-labelledby="enrollmentConfirmTitle">
    <div class="mx-auto w-full max-w-md overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
        <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">Enrollment</p>
            <h3 id="enrollmentConfirmTitle" class="mt-1 text-lg font-bold tracking-tight text-white">Confirm enrollment</h3>
        </div>
        <div class="space-y-4 px-6 py-5">
            <p id="enrollmentConfirmMessage" class="text-sm leading-relaxed text-gray-600"></p>
            <p id="enrollmentConfirmHint" class="text-xs text-gray-500">A vacant section will be assigned automatically. If all matching sections are full, a new section will be created. Student login accounts are created when needed.</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
            <button type="button" id="enrollmentConfirmCancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
            <button type="button" id="enrollmentConfirmSubmit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Confirm enrollment</button>
        </div>
    </div>
</div>

@if ($enrollmentResult)
    @php
        $resultReturnUrl = $returnSectionUrl ?? null;
    @endphp
    <div id="enrollmentResultModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4 pt-24" role="dialog" aria-modal="true" aria-labelledby="enrollmentResultTitle" @if ($resultReturnUrl) data-return-url="{{ $resultReturnUrl }}" @endif>
        <div class="mx-auto w-full max-w-sm overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
            <div class="px-6 py-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"></path></svg>
                </div>
                <h3 id="enrollmentResultTitle" class="text-lg font-bold text-gray-900">Enrollment confirmed</h3>
                <p class="mt-2 text-sm text-gray-500">The enrollment has been processed successfully.</p>
                @if (! empty($enrollmentResult['created_sections']))
                    <p class="mt-2 text-sm text-gray-500">New section created because matching sections were full: {{ implode(', ', $enrollmentResult['created_sections']) }}.</p>
                @endif
            </div>
            <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                <button type="button" id="enrollmentResultDismiss" class="w-full rounded-lg px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Done</button>
            </div>
        </div>
    </div>
@endif

@if ($confirmContext === 'single' && isset($enrollment))
    <div id="enrollmentStatusModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/70 p-4 pt-24" role="dialog" aria-modal="true" aria-labelledby="enrollmentStatusTitle">
        <div class="mx-auto w-full max-w-md overflow-hidden rounded-lg border border-gray-300 bg-white shadow-2xl">
            <div class="border-b border-gray-300 bg-[#296374] px-6 py-4">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/70">Enrollment</p>
                <h3 id="enrollmentStatusTitle" class="mt-1 text-lg font-bold tracking-tight text-white">Update enrollment status</h3>
            </div>
            <form id="enrollment-status-form" action="{{ route('guidance.enrollments.status', $enrollment) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="step" value="{{ $activeStep ?? 'enrollment' }}" data-guidance-step-field>
                @if (! empty($fromSectionId))
                    <input type="hidden" name="from_section" value="{{ $fromSectionId }}">
                @endif
                <div class="space-y-4 px-6 py-5">
                    <p class="text-sm leading-relaxed text-gray-600">Choose a new status for <strong class="text-gray-900">{{ $studentDisplayName }}</strong>.</p>
                    <label for="enrollment-status-select" class="block text-xs font-bold uppercase tracking-wider text-gray-500">Enrollment status</label>
                    <select name="status" id="enrollment-status-select" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/10">
                        @foreach (\App\Models\EnrollmentStatus::options() as $slug => $label)
                            <option value="{{ $slug }}" @selected(($enrollment->enrollment_status ?? '') === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p id="enrollmentStatusSameError" class="hidden text-sm font-medium text-red-600">This enrollment is already that status.</p>
                    <p class="text-xs text-gray-500">Choosing Enrolled or Temporarily Enrolled will assign a vacant section if this student does not have one yet.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <button type="button" id="enrollmentStatusCancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-lg px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:opacity-95" style="background-color: #296374;">Update status</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endpush

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var statusLabels = @json($statusLabels);
        var confirmModal = document.getElementById('enrollmentConfirmModal');
        var confirmMessage = document.getElementById('enrollmentConfirmMessage');
        var confirmCancel = document.getElementById('enrollmentConfirmCancel');
        var confirmSubmit = document.getElementById('enrollmentConfirmSubmit');
        var pendingSubmit = null;

        function openConfirmModal(message, onConfirm, showSectionHint, title) {
            if (!confirmModal || !confirmMessage || !confirmSubmit) {
                onConfirm();
                return;
            }

            confirmMessage.innerHTML = message;
            pendingSubmit = onConfirm;

            var confirmTitle = document.getElementById('enrollmentConfirmTitle');
            if (confirmTitle) {
                confirmTitle.textContent = title || 'Confirm enrollment';
            }

            var confirmHint = document.getElementById('enrollmentConfirmHint');
            if (confirmHint) {
                confirmHint.classList.toggle('hidden', showSectionHint === false);
            }

            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
        }

        function closeConfirmModal() {
            if (!confirmModal) {
                return;
            }

            confirmModal.classList.add('hidden');
            confirmModal.classList.remove('flex');
            pendingSubmit = null;
        }

        if (confirmCancel) {
            confirmCancel.addEventListener('click', closeConfirmModal);
        }

        if (confirmModal) {
            confirmModal.addEventListener('click', function (event) {
                if (event.target === confirmModal) {
                    closeConfirmModal();
                }
            });
        }

        if (confirmSubmit) {
            confirmSubmit.addEventListener('click', function () {
                if (typeof pendingSubmit === 'function') {
                    pendingSubmit();
                }
                closeConfirmModal();
            });
        }

        var resultModal = document.getElementById('enrollmentResultModal');
        var resultDismiss = document.getElementById('enrollmentResultDismiss');

        function closeResultModal() {
            if (!resultModal) {
                return;
            }

            var returnUrl = resultModal.getAttribute('data-return-url');
            if (returnUrl) {
                window.location.href = returnUrl;
                return;
            }

            resultModal.classList.add('hidden');
            resultModal.classList.remove('flex');
        }

        if (resultDismiss) {
            resultDismiss.addEventListener('click', closeResultModal);
        }

        if (resultModal) {
            resultModal.addEventListener('click', function (event) {
                if (event.target === resultModal) {
                    closeResultModal();
                }
            });
        }

        @if ($confirmContext === 'single')
            var currentStatus = @json($currentEnrollmentStatus ?? '');
            var settingsRoot = document.getElementById('enrollmentSettings');
            var settingsButton = document.getElementById('enrollmentSettingsButton');
            var settingsMenu = document.getElementById('enrollmentSettingsMenu');
            var openStatusButton = document.getElementById('openEnrollmentStatusModal');
            var statusModal = document.getElementById('enrollmentStatusModal');
            var statusForm = document.getElementById('enrollment-status-form');
            var statusSelect = document.getElementById('enrollment-status-select');
            var statusCancel = document.getElementById('enrollmentStatusCancel');
            var statusSameError = document.getElementById('enrollmentStatusSameError');

            function closeSettingsMenu() {
                if (!settingsMenu || !settingsButton) {
                    return;
                }

                settingsMenu.classList.add('hidden');
                settingsButton.setAttribute('aria-expanded', 'false');
            }

            function toggleSettingsMenu() {
                if (!settingsMenu || !settingsButton) {
                    return;
                }

                var isHidden = settingsMenu.classList.contains('hidden');
                settingsMenu.classList.toggle('hidden', !isHidden);
                settingsButton.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            }

            function openStatusModal() {
                if (!statusModal) {
                    return;
                }

                closeSettingsMenu();

                if (statusSameError) {
                    statusSameError.classList.add('hidden');
                }

                statusModal.classList.remove('hidden');
                statusModal.classList.add('flex');

                if (statusSelect) {
                    statusSelect.focus();
                }
            }

            function closeStatusModal() {
                if (!statusModal) {
                    return;
                }

                statusModal.classList.add('hidden');
                statusModal.classList.remove('flex');
            }

            if (settingsButton) {
                settingsButton.addEventListener('click', function (event) {
                    event.stopPropagation();
                    toggleSettingsMenu();
                });
            }

            if (openStatusButton) {
                openStatusButton.addEventListener('click', openStatusModal);
            }

            if (statusCancel) {
                statusCancel.addEventListener('click', closeStatusModal);
            }

            if (statusModal) {
                statusModal.addEventListener('click', function (event) {
                    if (event.target === statusModal) {
                        closeStatusModal();
                    }
                });
            }

            document.addEventListener('click', function (event) {
                if (settingsRoot && !settingsRoot.contains(event.target)) {
                    closeSettingsMenu();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                if (statusModal && !statusModal.classList.contains('hidden')) {
                    closeStatusModal();
                    return;
                }

                closeSettingsMenu();
            });

            if (statusForm && statusSelect) {
                statusForm.addEventListener('submit', function (event) {
                    var status = statusSelect.value;
                    var label = statusLabels[status] || status;

                    if (status === currentStatus) {
                        event.preventDefault();

                        if (statusSameError) {
                            statusSameError.textContent = 'This enrollment is already ' + label + '.';
                            statusSameError.classList.remove('hidden');
                        }

                        return;
                    }

                    if (statusSameError) {
                        statusSameError.classList.add('hidden');
                    }
                });
            }

            @if ($errors->has('status'))
                openStatusModal();
            @endif
        @else
            var bulkForm = document.querySelector('form[action*="bulk-approve"]');
            var bulkStatusInput = document.getElementById('bulk-status');

            if (bulkForm && bulkStatusInput) {
                bulkForm.querySelectorAll('.bulk-enroll-trigger').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var status = button.getAttribute('data-bulk-status') || 'enrolled';
                        var label = statusLabels[status] || status;
                        var selected = bulkForm.querySelectorAll('.row-checkbox:checked').length;

                        if (selected === 0) {
                            window.alert('Select at least one enrollment first.');
                            return;
                        }

                        var noun = selected === 1 ? 'enrollment' : 'enrollments';

                        openConfirmModal(
                            'Confirm enrolling <strong class="text-gray-900">' + selected + '</strong> selected ' + noun + ' as <strong class="text-[#296374]">' + label + '</strong>?',
                            function () {
                                bulkStatusInput.value = status;
                                bulkForm.submit();
                            }
                        );
                    });
                });

                var bulkChoice = document.getElementById('bulk-status-choice');
                var bulkApply = document.getElementById('bulk-status-apply');
                var activeStatuses = @json($activeStatusSlugs);

                if (bulkChoice && bulkApply) {
                    bulkApply.addEventListener('click', function () {
                        var status = bulkChoice.value;
                        var selected = bulkForm.querySelectorAll('.row-checkbox:checked').length;

                        if (!status) {
                            window.alert('Select a status first.');
                            return;
                        }

                        if (selected === 0) {
                            window.alert('Select at least one enrollment first.');
                            return;
                        }

                        var label = statusLabels[status] || status;
                        var noun = selected === 1 ? 'enrollment' : 'enrollments';
                        var isActive = activeStatuses.indexOf(status) !== -1;

                        openConfirmModal(
                            'Change <strong class="text-gray-900">' + selected + '</strong> selected ' + noun + ' to <strong class="text-[#296374]">' + label + '</strong>?',
                            function () {
                                bulkStatusInput.value = status;
                                bulkForm.submit();
                            },
                            isActive,
                            'Confirm status change'
                        );
                    });
                }
            }
        @endif
    });
</script>
