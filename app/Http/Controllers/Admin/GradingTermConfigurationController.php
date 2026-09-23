<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGradingTermRequest;
use App\Http\Requests\Admin\UpdateGradingTermSettingsRequest;
use App\Http\Requests\Admin\UpdateSeniorHighGradingTermRequest;
use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use App\Support\GradingTermNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GradingTermConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $request->string('tab')->toString() === 'senior_high'
            ? 'senior_high'
            : 'junior_high';

        return view('users.admin.grading-term-config', [
            'activeTab' => $activeTab,
            'terms' => GradingTerm::query()
                ->with(['juniorHighStatus', 'seniorHighStatus'])
                ->orderBy('sort_order')
                ->orderBy('term_ID')
                ->get(),
            'settings' => GradingTermSetting::current(),
            'configuredPeriods' => GradingTerm::configuredPeriods(),
            'openPeriods' => GradingTerm::gradingOpenPeriods(),
            'seniorHighPeriods' => GradingTerm::seniorHighPeriods(),
            'seniorHighSemesters' => GradingSemester::query()
                ->with('status')
                ->whereIn('key', [GradingSemester::FIRST, GradingSemester::SECOND])
                ->orderBy('sort_order')
                ->orderBy('semester_ID')
                ->get(),
            'seniorHighTerms' => GradingTerm::query()
                ->with('seniorHighStatus')
                ->whereIn('term_ID', array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID'))))
                ->orderBy('sort_order')
                ->orderBy('term_ID')
                ->get(),
            'currentSeniorHighPeriod' => GradingTerm::currentSeniorHighPeriod(),
            'lockedSeniorHighPeriodKeys' => GradingTerm::lockedSeniorHighPeriodKeys(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $settings = GradingTermSetting::current();
        $termCount = GradingTerm::query()->count();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50', 'unique:grading_terms,label'],
        ]);

        $nextTermNumber = $termCount + 1;
        $nextOrder = (int) GradingTerm::query()->max('sort_order') + 1;

        GradingTerm::query()->create([
            'key' => 'term_'.$nextTermNumber,
            'label' => $validated['label'],
            'sort_order' => $nextOrder,
            'junior_high_grading_period_status_ID' => GradingPeriodStatus::archivedId(),
            'senior_high_grading_period_status_ID' => GradingPeriodStatus::archivedId(),
        ]);

        GradingTerm::syncActiveStatus();

        return back()->with('success', 'Term added successfully.');
    }

    public function update(UpdateGradingTermRequest $request, GradingTerm $term): RedirectResponse
    {
        $term->update($request->validated());

        GradingTerm::syncActiveStatus();

        return back()->with('success', 'Term updated successfully.');
    }

    public function updateJuniorHighStatus(Request $request, GradingTerm $term): RedirectResponse
    {
        $validated = $this->validateTermStatus($request);

        if (! $this->isWithinJuniorHighLimit($term) && $validated['status'] !== GradingPeriodStatus::ARCHIVED) {
            return back()->withErrors(['status' => 'Increase the maximum terms before making this term available.']);
        }

        if ($this->isWithinJuniorHighLimit($term) && $validated['status'] === GradingPeriodStatus::ARCHIVED) {
            return back()->withErrors(['status' => 'Reduce the maximum terms to archive this term.']);
        }

        if ($validated['status'] === GradingPeriodStatus::OPEN) {
            GradingTerm::query()
                ->where('term_ID', '!=', $term->term_ID)
                ->where('junior_high_grading_period_status_ID', GradingPeriodStatus::openId())
                ->update(['junior_high_grading_period_status_ID' => GradingPeriodStatus::activeId()]);
        }

        $term->update([
            'junior_high_grading_period_status_ID' => GradingPeriodStatus::idFor($validated['status']),
        ]);

        GradingTerm::syncActiveStatus();

        return back()->with('success', "{$term->label} is now ".GradingPeriodStatus::nameFor($validated['status']).'.');
    }

    public function closeAllJuniorHighTerms(): RedirectResponse
    {
        GradingTerm::closeAllJuniorHighTerms();

        return back()->with('success', 'All configured Junior High terms are now closed.');
    }

    public function updateSeniorHighStatus(Request $request, GradingTerm $term): RedirectResponse
    {
        $validated = $this->validateTermStatus($request);
        $seniorHighTermIds = array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID')));

        if (! in_array((int) $term->term_ID, $seniorHighTermIds, true) && $validated['status'] !== GradingPeriodStatus::ARCHIVED) {
            return back()->withErrors(['status' => 'Only the first three terms can be used for Senior High.']);
        }

        if ($validated['status'] === GradingPeriodStatus::OPEN) {
            GradingTerm::query()
                ->where('term_ID', '!=', $term->term_ID)
                ->where('senior_high_grading_period_status_ID', GradingPeriodStatus::openId())
                ->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::activeId()]);
            GradingTermSetting::current()->update(['term_ID' => $term->term_ID]);
        }

        $term->update([
            'senior_high_grading_period_status_ID' => GradingPeriodStatus::idFor($validated['status']),
        ]);

        return back()->with('success', "Senior High {$term->label} is now ".GradingPeriodStatus::nameFor($validated['status']).'.');
    }

    /** @return array{status: string} */
    private function validateTermStatus(Request $request): array
    {
        return $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', GradingPeriodStatus::slugs())],
        ]);
    }

    private function isWithinJuniorHighLimit(GradingTerm $term): bool
    {
        return GradingTerm::query()
            ->orderBy('sort_order')
            ->orderBy('term_ID')
            ->limit(GradingTermSetting::current()->max_terms)
            ->pluck('term_ID')
            ->contains($term->term_ID);
    }

    private function openSeniorHighTerm(int $termId): void
    {
        GradingTerm::query()
            ->where('term_ID', '!=', $termId)
            ->where('senior_high_grading_period_status_ID', GradingPeriodStatus::openId())
            ->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::activeId()]);

        GradingTerm::query()->where('term_ID', $termId)->update([
            'senior_high_grading_period_status_ID' => GradingPeriodStatus::openId(),
        ]);
    }

    public function updateSettings(UpdateGradingTermSettingsRequest $request): RedirectResponse
    {
        $maxTerms = (int) $request->validated('max_terms');
        $settings = GradingTermSetting::current();
        $openTermsCount = min((int) $settings->open_terms_count, $maxTerms);

        $settings->update([
            'max_terms' => $maxTerms,
            'open_terms_count' => max(1, $openTermsCount),
        ]);

        GradingTerm::syncActiveStatus($maxTerms);

        return back()->with('success', 'Maximum terms updated successfully.');
    }

    public function updateOpenTerm(Request $request): RedirectResponse
    {
        $settings = GradingTermSetting::current();
        $previousOpenTermsCount = (int) $settings->open_terms_count;
        $configuredTermCount = GradingTerm::query()->juniorHighAvailable()->count();
        $configuredTermCount = max(1, $configuredTermCount);

        $validated = $request->validate([
            'open_terms_count' => ['required', 'integer', 'min:1', 'max:'.$configuredTermCount],
        ]);

        $settings->update([
            'open_terms_count' => (int) $validated['open_terms_count'],
        ]);

        $currentLabel = GradingTerm::currentEditablePeriodLabel() ?? 'Term 1';

        if ((int) $validated['open_terms_count'] > $previousOpenTermsCount) {
            GradingTermNotifier::opened($currentLabel, false);
        }

        return back()->with('success', "School year grading is now open through {$currentLabel}. Earlier terms are locked for teachers.");
    }

    public function updateSeniorHigh(UpdateSeniorHighGradingTermRequest $request): RedirectResponse
    {
        $previous = GradingTerm::currentSeniorHighPeriod();
        $semester = $request->validated('semester');
        $termNumber = (int) $request->validated('term');
        $period = GradingTerm::seniorHighPeriod($semester, $termNumber);

        if (($period['is_active'] ?? true) === false) {
            return back()->withErrors([
                'semester' => 'The selected senior high period is inactive or invalid.',
                'term' => 'The selected senior high period is inactive or invalid.',
            ]);
        }

        $this->openSeniorHighTerm((int) ($period['term_ID'] ?? 0));
        GradingTermSetting::current()->setSeniorHighPeriod($semester, $termNumber);

        $currentLabel = GradingTerm::currentSeniorHighPeriodLabel();

        if (GradingTerm::seniorHighPeriodPosition($period['semester'], $period['term'])
            > GradingTerm::seniorHighPeriodPosition($previous['semester'], $previous['term'])) {
            GradingTermNotifier::opened($currentLabel, true);
        }

        return back()->with('success', "Senior high grading is now open through {$currentLabel}. Earlier periods are locked for teachers.");
    }

    public function updateSeniorHighSemester(Request $request): RedirectResponse
    {
        $previous = GradingTerm::currentSeniorHighPeriod();
        $validated = $request->validate([
            'semester_ID' => ['required', 'integer', 'exists:grading_semesters,semester_ID'],
        ]);

        $semester = GradingSemester::query()
            ->active()
            ->whereIn('key', [GradingSemester::FIRST, GradingSemester::SECOND])
            ->findOrFail($validated['semester_ID']);

        GradingTermSetting::current()->update(['semester_ID' => $semester->semester_ID]);

        $current = GradingTerm::currentSeniorHighPeriod();
        if (GradingTerm::seniorHighPeriodPosition($current['semester'], $current['term'])
            > GradingTerm::seniorHighPeriodPosition($previous['semester'], $previous['term'])) {
            GradingTermNotifier::opened($current['label'], true);
        }

        return back()->with('success', "Active senior high semester set to {$semester->label}.");
    }

    public function closeSeniorHighSemester(Request $request, GradingSemester $semester): RedirectResponse
    {
        abort_unless(in_array($semester->key, [GradingSemester::FIRST, GradingSemester::SECOND], true), 404);

        DB::transaction(function () use ($semester): void {
            $semester->update(['grading_period_status_ID' => GradingPeriodStatus::closedId()]);

            GradingTerm::query()
                ->whereIn('term_ID', array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID'))))
                ->seniorHighAvailable()
                ->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::closedId()]);
        });

        return back()->with('success', "{$semester->label} is closed, and all of its Senior High terms are closed.");
    }

    public function updateSeniorHighTerm(Request $request): RedirectResponse
    {
        $previous = GradingTerm::currentSeniorHighPeriod();
        $seniorHighTermIds = array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID')));

        $validated = $request->validate([
            'term_ID' => ['required', 'integer', 'exists:grading_terms,term_ID'],
        ]);

        $term = GradingTerm::query()
            ->whereIn('term_ID', $seniorHighTermIds)
            ->findOrFail($validated['term_ID']);

        $this->openSeniorHighTerm((int) $term->term_ID);
        GradingTermSetting::current()->update(['term_ID' => $term->term_ID]);

        $current = GradingTerm::currentSeniorHighPeriod();
        if (GradingTerm::seniorHighPeriodPosition($current['semester'], $current['term'])
            > GradingTerm::seniorHighPeriodPosition($previous['semester'], $previous['term'])) {
            GradingTermNotifier::opened($current['label'], true);
        }

        return back()->with('success', "Active senior high term set to {$term->label}.");
    }
}
