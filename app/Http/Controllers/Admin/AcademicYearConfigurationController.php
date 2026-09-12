<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->orderByDesc('status')
            ->orderByDesc('start_date')
            ->orderByDesc('SY_ID')
            ->paginate($perPage)
            ->withQueryString();

        $totalYears = AcademicYear::query()->count();
        $activeCount = AcademicYear::query()->where('status', true)->count();

        return view('users.admin.academic-year-config', [
            'academicYears' => $academicYears,
            'perPage' => $perPage,
            'totalYears' => $totalYears,
            'activeCount' => $activeCount,
            'inactiveCount' => $totalYears - $activeCount,
            'currentYear' => AcademicYear::query()
                ->where('status', true)
                ->orderByDesc('start_date')
                ->orderByDesc('SY_ID')
                ->first(),
        ]);
    }

    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        AcademicYear::query()->create([
            'school_year' => $validated['school_year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => false,
        ]);

        return back()->with('success', 'Academic year created successfully.');
    }

    public function update(StoreAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validated();

        $academicYear->update([
            'school_year' => $validated['school_year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        return back()->with('success', 'Academic year updated successfully.');
    }

    public function toggleStatus(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->status) {
            $academicYear->update(['status' => false]);

            return back()->with('success', 'Academic year archived successfully.');
        }

        $academicYear->makeActive();

        return back()->with('success', 'Academic year activated successfully.');
    }
}
