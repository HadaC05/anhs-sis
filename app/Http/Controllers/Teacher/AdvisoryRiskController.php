<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradingTerm;
use App\Models\Section;
use App\Models\StudentSubjectGrade;
use App\Notifications\AcademicSupportReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdvisoryRiskController extends Controller
{
    private function context(Request $request, Section $section): array
    {
        abort_unless($request->user()?->staff_id && $section->staff_ID === $request->user()->staff_id, 403);
        $section->load(['academicYear', 'gradeLevel']);
        $periods = collect(GradingTerm::openPeriodsForSection($section));
        $request->validate(['term' => ['nullable', Rule::in($periods->pluck('key')->all())]]);
        $term = $request->input('term') ?: (GradingTerm::currentEditablePeriodKeyForSection($section) ?? $periods->last()['key'] ?? null);

        return [$periods, $term];
    }

    private function grades(Section $section, ?string $term): Collection
    {
        if (! $term) {
            return collect();
        }

        return StudentSubjectGrade::query()
            ->with(['studentSubject.enrollment.student.application', 'assignment.curriculumSubject.subject'])
            ->forPeriodKey($term)
            ->whereHas('assignment', fn ($query) => $query->withoutMapehParents()->where('section_ID', $section->section_ID)->where('SY_ID', $section->SY_ID))
            ->whereHas('studentSubject.enrollment', fn ($query) => $query
                ->where('section_ID', $section->section_ID)->where('SY_ID', $section->SY_ID)
                ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds()))
            ->whereNotNull('numeric_grade')->where('numeric_grade', '<', 75)
            ->get()->groupBy(fn ($grade) => $grade->studentSubject->enrollment_ID);
    }

    public function index(Request $request, Section $section)
    {
        [$periods, $term] = $this->context($request, $section);
        $rows = $this->grades($section, $term)->map(function ($grades) {
            $enrollment = $grades->first()->studentSubject->enrollment;

            return ['enrollment' => $enrollment, 'student' => $enrollment->student, 'grades' => $grades];
        })->sortBy(fn ($row) => $row['student']->last_name);

        return view('users.teacher.advisory.at-risk', compact('section', 'periods', 'term', 'rows'));
    }

    public function notify(Request $request, Section $section, Enrollment $enrollment)
    {
        [$periods, $term] = $this->context($request, $section);
        abort_unless($enrollment->section_ID === $section->section_ID && $enrollment->SY_ID === $section->SY_ID, 404);
        $grades = $this->grades($section, $term)->get($enrollment->enrollment_ID);
        if (! $grades) {
            return back()->withErrors(['student' => 'This learner no longer has recorded grades below 75 for this period.']);
        }

        $reminder = new AcademicSupportReminder($enrollment->enrollment_ID, $periods->firstWhere('key', $term)['label'], $section->academicYear->school_year, $term);
        $student = DB::transaction(function () use ($enrollment, $term, $reminder) {
            $student = $enrollment->student()->lockForUpdate()->firstOrFail();
            $recent = $student->notifications()->where('type', AcademicSupportReminder::class)
                ->where('created_at', '>=', now()->subDay())->get()
                ->contains(fn ($notification) => ($notification->data['enrollment_ID'] ?? null) === $enrollment->enrollment_ID
                    && ($notification->data['period_key'] ?? null) === $term);
            if ($recent) {
                return null;
            }
            $student->notifyNow($reminder, ['database']);

            return $student;
        });

        if (! $student) {
            return back()->with('warning', 'This student has already been notified for this period in the last 24 hours. No additional notification or email was sent.');
        }

        if (blank($student->email)) {
            return back()->with('status', 'In-app notification sent. No email was sent because the student has no email address.');
        }

        // Keep the in-app reminder even if the email service is unavailable.
        try {
            $student->notifyNow($reminder, ['mail']);
        } catch (\Exception $exception) {
            report($exception);

            return back()->with('warning', 'In-app notification sent, but the email could not be sent. Please check the email service.');
        }

        return back()->with('status', 'In-app notification and email sent to the student.');
    }
}
