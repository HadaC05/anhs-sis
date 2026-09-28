<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicSetupController extends Controller
{
    public function index(Request $request): View
    {
        return view('users.admin.academic-setup', [
            'yearData' => app(AcademicYearConfigurationController::class)->pageData($request),
            'termData' => app(GradingTermConfigurationController::class)->pageData($request),
            'setupTab' => $request->routeIs('*.grading-term-config.*')
                ? 'grading-term-config'
                : 'academic-year-config',
        ]);
    }
}
