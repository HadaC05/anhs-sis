<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicYearConfigurationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->integer('per_page', 10);
        if (! in_array($perPage, [5, 10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();

        $academicYears = AcademicYear::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('school_year', 'like', "%{$search}%");
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status): void {
                $query->where('status', $status === 'active');
            })
            ->orderByDesc('SY_ID')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.admin.academic-year-config', [
            'academicYears' => $academicYears,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_year' => ['required', 'string', 'max:255', 'unique:academic_years,school_year'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        AcademicYear::query()->create([
            'school_year' => $validated['school_year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => true,
        ]);

        return back()->with('success', 'Academic year created successfully.');
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validate([
            'school_year' => ['required', 'string', 'max:255', Rule::unique('academic_years', 'school_year')->ignore($academicYear->SY_ID, 'SY_ID')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $academicYear->update($validated);

        return back()->with('success', 'Academic year updated successfully.');
    }

    public function toggleStatus(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update(['status' => ! $academicYear->status]);

        return back()->with('success', $academicYear->status
            ? 'Academic year activated successfully.'
            : 'Academic year archived successfully.');
    }
}
