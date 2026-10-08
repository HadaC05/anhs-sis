<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\RemediationCase;
use App\Models\Section;
use App\Support\RemediationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RemediationController extends Controller
{
    public function startGuidance(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $case = RemediationManager::start($enrollment, $request->user());

        return redirect()->route('guidance.remediations.show', $case)
            ->with('success', 'Remediation case started.');
    }

    public function startTeacher(Request $request, Section $section, Enrollment $enrollment): RedirectResponse
    {
        $this->authorizeTeacher($request, $section, $enrollment);
        $case = RemediationManager::start($enrollment, $request->user());

        return redirect()->route('teacher.advisory.remediations.show', [$section, $case])
            ->with('status', 'Remediation case started.');
    }

    public function showGuidance(RemediationCase $remediationCase): View
    {
        return $this->view($remediationCase, 'guidance');
    }

    public function showTeacher(Request $request, Section $section, RemediationCase $remediationCase): View
    {
        $this->authorizeTeacher($request, $section, $remediationCase->enrollment);

        return $this->view($remediationCase, 'teacher', $section);
    }

    public function showPrincipal(RemediationCase $remediationCase): View
    {
        return $this->view($remediationCase, 'principal');
    }

    public function updateGuidance(Request $request, RemediationCase $remediationCase): RedirectResponse
    {
        $this->save($request, $remediationCase);

        return back()->with('success', $request->input('action') === 'submit' ? 'Remediation results submitted for principal approval.' : 'Remediation draft saved.');
    }

    public function updateTeacher(Request $request, Section $section, RemediationCase $remediationCase): RedirectResponse
    {
        $this->authorizeTeacher($request, $section, $remediationCase->enrollment);
        $this->save($request, $remediationCase);

        return back()->with('status', $request->input('action') === 'submit' ? 'Remediation results submitted for principal approval.' : 'Remediation draft saved.');
    }

    public function approve(Request $request, RemediationCase $remediationCase): RedirectResponse
    {
        $case = RemediationManager::approve($remediationCase, $request->user());
        $message = $case->status === RemediationCase::APPROVED_PASSED
            ? 'Remediation approved. The learner is now eligible for promotion or completion processing.'
            : 'Remediation approved with an RFG below 75. The learner requires intervention review and was not promoted automatically.';

        return back()->with('success', $message);
    }

    private function save(Request $request, RemediationCase $case): void
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'subjects' => ['required', 'array'],
            'subjects.*.remedial_class_mark' => ['nullable', 'numeric', 'between:0,100'],
            'subjects.*.remarks' => ['nullable', 'string', 'max:255'],
            'action' => ['required', 'in:save,submit'],
        ]);

        RemediationManager::save($case->load('subjects'), $validated, $validated['action'] === 'submit');
    }

    private function view(RemediationCase $case, string $portal, ?Section $section = null): View
    {
        $case->load(['enrollment.student.application', 'enrollment.academicYear', 'enrollment.gradeLevel', 'subjects.subject', 'starter', 'approver']);

        return view('remediations.show', [
            'case' => $case,
            'portal' => $portal,
            'section' => $section,
            'editable' => in_array($portal, ['guidance', 'teacher'], true) && $case->status === RemediationCase::IN_PROGRESS,
        ]);
    }

    private function authorizeTeacher(Request $request, Section $section, Enrollment $enrollment): void
    {
        abort_unless(
            (int) $section->staff_ID === (int) $request->user()?->staff_id
            && (int) $enrollment->section_ID === (int) $section->section_ID,
            403,
        );
    }
}
