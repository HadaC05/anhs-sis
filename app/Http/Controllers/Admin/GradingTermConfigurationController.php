<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGradingTermRequest;
use App\Http\Requests\Admin\UpdateGradingTermSettingsRequest;
use App\Http\Requests\Admin\UpdateSeniorHighGradingTermRequest;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use App\Models\StudentObservedValue;
use App\Models\StudentSubjectGrade;
use App\Support\GradingTermNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradingTermConfigurationController extends Controller
{
    public function pageData(Request $request): array
    {
        $activeTab = $request->string('tab')->toString() === 'senior_high'
            ? 'senior_high'
            : 'junior_high';

        return [
            'currentYear' => AcademicYear::query()
                ->where('status', true)
                ->orderByDesc('start_date')
                ->orderByDesc('SY_ID')
                ->first(),
            'activeTab' => $activeTab,
            'terms' => GradingTerm::query()->juniorHigh()
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
            'seniorHighTerms' => GradingTerm::query()->seniorHigh()
                ->with('seniorHighStatus')
                ->orderBy('sort_order')
                ->orderBy('term_ID')
                ->get(),
            'currentSeniorHighPeriod' => GradingTerm::currentSeniorHighPeriod(),
            'lockedSeniorHighPeriodKeys' => GradingTerm::lockedSeniorHighPeriodKeys(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $settings = GradingTermSetting::current();
        $termCount = GradingTerm::query()->juniorHigh()->count();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50', Rule::unique('grading_terms', 'label')->where('school_level', 'junior_high')],
        ]);

        $nextTermNumber = $termCount + 1;
        $nextOrder = (int) GradingTerm::query()->juniorHigh()->max('sort_order') + 1;

        GradingTerm::query()->juniorHigh()->create([
            'school_level' => 'junior_high',
            'key' => 'term_'.$nextTermNumber,
            'label' => $validated['label'],
            'sort_order' => $nextOrder,
            'junior_high_grading_period_status_ID' => GradingPeriodStatus::archivedId(),
            'senior_high_grading_period_status_ID' => null,
        ]);

        GradingTerm::syncActiveStatus();

        return back()->with('success', 'Term added successfully.');
    }

    public function update(UpdateGradingTermRequest $request, GradingTerm $term): RedirectResponse
    {
        $term->update($request->validated());

        if ($term->school_level === 'junior_high') {
            GradingTerm::syncActiveStatus();
        }

        return back()->with('success', 'Term updated successfully.');
    }

    public function storeSeniorHigh(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50', Rule::unique('grading_terms', 'label')->where('school_level', 'senior_high')],
        ]);

        DB::transaction(function () use ($validated): void {
            GradingTermSetting::current()->newQuery()->whereKey(1)->lockForUpdate()->first();
            $nextOrder = (int) GradingTerm::query()->seniorHigh()->max('sort_order') + 1;
            $number = $nextOrder;
            while (GradingTerm::query()->seniorHigh()->where('key', 'term_'.$number)->exists()) {
                $number++;
            }
            GradingTerm::query()->seniorHigh()->create([
                'school_level' => 'senior_high',
                'key' => 'term_'.$number,
                'label' => $validated['label'],
                'sort_order' => $nextOrder,
                'junior_high_grading_period_status_ID' => null,
                'senior_high_grading_period_status_ID' => GradingPeriodStatus::archivedId(),
            ]);
        });

        return back()->with('success', 'Term added. Increase the Senior High maximum terms to include it.');
    }

    public function updateSeniorHighSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'senior_high_max_terms' => ['required', 'integer', 'min:2', 'max:'.min(12, GradingTerm::query()->seniorHigh()->count())],
        ]);
        $limit = (int) $validated['senior_high_max_terms'];

        DB::transaction(function () use ($limit): void {
            $settings = GradingTermSetting::current()->newQuery()->whereKey(1)->lockForUpdate()->firstOrFail();
            $terms = GradingTerm::query()->seniorHigh()->orderBy('sort_order')->orderBy('term_ID')->lockForUpdate()->get();
            $excluded = $terms->slice($limit);
            if ($excluded->contains('term_ID', $settings->term_ID)) {
                throw ValidationException::withMessages(['senior_high_max_terms' => 'Select an earlier Senior High current term before reducing the maximum.']);
            }
            $periodKeys = $excluded->flatMap(fn ($term) => [$term->key, 'shs_sem1_'.$term->key, 'shs_sem2_'.$term->key])->all();
            $hasGrades = StudentSubjectGrade::query()->whereIn('term_ID', $excluded->pluck('term_ID'))
                ->whereHas('assignment.section', fn ($query) => $query->whereIn('grade_ID', [GradeLevel::idForValue('grade_11'), GradeLevel::idForValue('grade_12')]))->exists();
            $hasObservations = StudentObservedValue::query()->whereIn('grading_period', $periodKeys)
                ->whereHas('enrollment.section', fn ($query) => $query->whereIn('grade_ID', [GradeLevel::idForValue('grade_11'), GradeLevel::idForValue('grade_12')]))->exists();
            if ($hasGrades || $hasObservations) {
                throw ValidationException::withMessages(['senior_high_max_terms' => 'These terms have saved Senior High records. Keep them included to preserve grading and reports.']);
            }
            $previousLimit = (int) $settings->senior_high_max_terms;
            $settings->update(['senior_high_max_terms' => $limit]);
            foreach ($terms as $index => $term) {
                if ($index >= $limit) {
                    $term->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::archivedId()]);
                } elseif ($index >= $previousLimit && $term->isSeniorHighArchived()) {
                    $term->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::activeId()]);
                }
            }
        });

        return back()->with('success', 'Senior High maximum terms updated successfully.');
    }

    public function updateJuniorHighStatus(Request $request, GradingTerm $term): RedirectResponse
    {
        abort_unless($term->school_level === 'junior_high', 404);

        $validated = $this->validateTermStatus($request);

        if (! $this->isWithinJuniorHighLimit($term) && $validated['status'] !== GradingPeriodStatus::ARCHIVED) {
            return back()->withErrors(['status' => 'Increase the maximum terms before making this term available.']);
        }

        if ($this->isWithinJuniorHighLimit($term) && $validated['status'] === GradingPeriodStatus::ARCHIVED) {
            return back()->withErrors(['status' => 'Reduce the maximum terms to archive this term.']);
        }

        DB::transaction(function () use ($validated, $term): void {
            if ($validated['status'] === GradingPeriodStatus::OPEN) {
                $includedIds = GradingTerm::query()->juniorHigh()->orderBy('sort_order')->orderBy('term_ID')
                    ->limit(GradingTermSetting::current()->max_terms)->pluck('term_ID');
                GradingTerm::query()->juniorHigh()->whereIn('term_ID', $includedIds)->where('term_ID', '!=', $term->term_ID)
                    ->update(['junior_high_grading_period_status_ID' => GradingPeriodStatus::activeId()]);
            }
            $term->update([
                'junior_high_grading_period_status_ID' => GradingPeriodStatus::idFor($validated['status']),
            ]);
            GradingTerm::syncActiveStatus();
        });

        return back()->with('success', "{$term->label} is now ".GradingPeriodStatus::nameFor($validated['status']).'.');
    }

    public function closeAllJuniorHighTerms(): RedirectResponse
    {
        GradingTerm::closeAllJuniorHighTerms();

        return back()->with('success', 'All configured Junior High terms are now closed.');
    }

    public function updateSeniorHighStatus(Request $request, GradingTerm $term): RedirectResponse
    {
        abort_unless($term->school_level === 'senior_high', 404);

        $validated = $this->validateTermStatus($request);
        $seniorHighTermIds = array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID')));

        if (! in_array((int) $term->term_ID, $seniorHighTermIds, true) && $validated['status'] !== GradingPeriodStatus::ARCHIVED) {
            return back()->withErrors(['status' => 'Increase the Senior High maximum terms before making this term available.']);
        }

        if ($validated['status'] === GradingPeriodStatus::OPEN) {
            GradingTerm::query()->seniorHigh()
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
        return GradingTerm::query()->juniorHigh()
            ->orderBy('sort_order')
            ->orderBy('term_ID')
            ->limit(GradingTermSetting::current()->max_terms)
            ->pluck('term_ID')
            ->contains($term->term_ID);
    }

    private function openSeniorHighTerm(int $termId): void
    {
        GradingTerm::query()->seniorHigh()
            ->where('term_ID', '!=', $termId)
            ->where('senior_high_grading_period_status_ID', GradingPeriodStatus::openId())
            ->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::activeId()]);

        GradingTerm::query()->seniorHigh()->where('term_ID', $termId)->update([
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
        $configuredTermCount = GradingTerm::query()->juniorHigh()->juniorHighAvailable()->count();
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

            if ((int) GradingTermSetting::current()->semester_ID === (int) $semester->semester_ID) {
                GradingTerm::query()->seniorHigh()
                    ->whereIn('term_ID', array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID'))))
                    ->seniorHighAvailable()
                    ->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::closedId()]);
            }
        });

        return back()->with('success', "{$semester->label} is closed, and all of its Senior High terms are closed.");
    }

    public function updateSeniorHighSemesterStatus(Request $request, GradingSemester $semester): RedirectResponse
    {
        abort_unless(in_array($semester->key, [GradingSemester::FIRST, GradingSemester::SECOND], true), 404);
        $validated = $this->validateTermStatus($request);
        if ($validated['status'] === GradingPeriodStatus::CLOSED) {
            return $this->closeSeniorHighSemester($request, $semester);
        }

        DB::transaction(function () use ($validated, $semester): void {
            $settings = GradingTermSetting::current();
            if ($validated['status'] === GradingPeriodStatus::OPEN) {
                $termIds = array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID')));
                if ($termIds === []) {
                    throw ValidationException::withMessages(['status' => 'Add a Senior High term before opening a semester.']);
                }
                $currentTermId = in_array((int) $settings->term_ID, $termIds, true) ? (int) $settings->term_ID : $termIds[0];
                GradingSemester::query()->where('semester_ID', '!=', $semester->semester_ID)
                    ->where('grading_period_status_ID', GradingPeriodStatus::openId())
                    ->update(['grading_period_status_ID' => GradingPeriodStatus::activeId()]);
                GradingTerm::query()->seniorHigh()->whereIn('term_ID', $termIds)
                    ->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::activeId()]);
                GradingTerm::query()->seniorHigh()->whereNotIn('term_ID', $termIds)
                    ->update(['senior_high_grading_period_status_ID' => GradingPeriodStatus::archivedId()]);
                $this->openSeniorHighTerm($currentTermId);
                $settings->update(['semester_ID' => $semester->semester_ID, 'term_ID' => $currentTermId]);
            }
            $semester->update(['grading_period_status_ID' => GradingPeriodStatus::idFor($validated['status'])]);
        });

        return back()->with('success', $validated['status'] === GradingPeriodStatus::OPEN
            ? "{$semester->label} is open. Terms within the Senior High maximum are active, with the current term open for grading."
            : "{$semester->label} is now ".GradingPeriodStatus::nameFor($validated['status']).'.');
    }

    public function updateSeniorHighTerm(Request $request): RedirectResponse
    {
        $previous = GradingTerm::currentSeniorHighPeriod();
        $seniorHighTermIds = array_values(array_filter(array_column(GradingTerm::seniorHighTerms(), 'term_ID')));

        $validated = $request->validate([
            'term_ID' => ['required', 'integer', 'exists:grading_terms,term_ID'],
        ]);

        $term = GradingTerm::query()->seniorHigh()
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
