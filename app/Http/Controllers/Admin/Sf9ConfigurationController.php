<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradingTerm;
use App\Models\Section;
use App\Models\Sf9Configuration;
use App\Support\Sf9ReportCardBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class Sf9ConfigurationController extends Controller
{
    public function edit()
    {
        return view('users.admin.sf9-configuration', [
            'configuration' => Sf9Configuration::current(),
            'formats' => Sf9Configuration::formats(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(collect(Sf9Configuration::formats())
            ->map(fn ($formats) => ['required', 'string', Rule::in(array_keys($formats))])->all());
        Sf9Configuration::query()->updateOrCreate(['id' => 1], $data);

        return back()->with('status', 'SF9 formats saved. Future generation will use these selections.');
    }

    public function preview(string $format)
    {
        $level = collect(Sf9Configuration::formats())->search(fn ($formats) => isset($formats[$format]));
        abort_if($level === false, 404);
        $section = new Section;
        $section->grade_level = $level === 'senior_high' ? 'grade_11' : 'grade_7';
        $section->setRelation('academicYear', AcademicYear::query()->where('status', true)->orderByDesc('SY_ID')->first());
        $periods = GradingTerm::configuredPeriods();
        $card = Sf9ReportCardBuilder::buildCard(new Enrollment, $section, collect(), collect(), collect(), $periods);

        $card['name'] = 'SAMPLE, Learner';
        $card['section_name'] = 'Sample';

        return view('users.teacher.advisory.sf9-print', [
            'cards' => [$card],
            'periods' => $periods,
            'sf9Formats' => [$level => $format],
            'previewLabel' => Sf9Configuration::formats()[$level][$format],
            'observedValueMarkings' => Sf9ReportCardBuilder::observedValueMarkings(),
        ]);
    }
}
