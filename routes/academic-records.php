<?php

use App\Http\Controllers\Guidance\GuidanceDashboardController;
use App\Http\Controllers\Guidance\GuidanceEnrollmentController;
use App\Http\Controllers\Registrar\RegistrarDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/students', [RegistrarDashboardController::class, 'students'])
    ->name('students');
Route::get('/students/{student}', [RegistrarDashboardController::class, 'show'])
    ->name('students.show');
Route::get('/students/{student}/documents/{document}/view', [RegistrarDashboardController::class, 'viewDocument'])
    ->name('students.documents.view');
Route::get('/students/{student}/enrollments/{enrollment}/sf9', [RegistrarDashboardController::class, 'studentSf9'])
    ->name('students.sf9');
Route::get('/students/{student}/sf10', [RegistrarDashboardController::class, 'studentSf10'])
    ->name('students.sf10');
Route::get('/enrollments', [GuidanceDashboardController::class, 'enrollments'])
    ->name('enrollments.index');
Route::get('/enrollments/create', [GuidanceEnrollmentController::class, 'create'])
    ->name('enrollments.create');
Route::post('/enrollments', [GuidanceEnrollmentController::class, 'store'])
    ->name('enrollments.store');
Route::get('/enrollments/check-lrn', [GuidanceEnrollmentController::class, 'checkLrn'])
    ->name('enrollments.check-lrn');
Route::get('/enrollments/check-email', [GuidanceEnrollmentController::class, 'checkEmail'])
    ->name('enrollments.check-email');
Route::get('/enrollments/{enrollment}/edit', [GuidanceEnrollmentController::class, 'edit'])
    ->name('enrollments.edit');
Route::put('/enrollments/{enrollment}', [GuidanceEnrollmentController::class, 'update'])
    ->name('enrollments.update');
Route::get('/enrollments/{enrollment}', [GuidanceDashboardController::class, 'show'])
    ->name('enrollments.show');
Route::patch('/enrollments/{enrollment}/placement-test', [GuidanceDashboardController::class, 'updatePlacementTestRecommendation'])
    ->name('enrollments.placement-test');
Route::post('/enrollments/{enrollment}/approve', [GuidanceDashboardController::class, 'approve'])
    ->name('enrollments.approve');
Route::post('/enrollments/{enrollment}/confirm', [GuidanceDashboardController::class, 'confirmEnrollment'])
    ->name('enrollments.confirm');
Route::patch('/enrollments/{enrollment}/status', [GuidanceDashboardController::class, 'updateStatus'])
    ->name('enrollments.status');
Route::post('/enrollments/bulk-approve', [GuidanceDashboardController::class, 'bulkApprove'])
    ->name('enrollments.bulk-approve');
Route::get('/enrollments/{enrollment}/print', [GuidanceDashboardController::class, 'print'])
    ->name('enrollments.print');
Route::post('/enrollments/print', [GuidanceDashboardController::class, 'printMultiple'])
    ->name('enrollments.print-multiple');
Route::post('/documents/bulk-verify', [GuidanceDashboardController::class, 'bulkVerifyDocuments'])
    ->name('documents.bulk-verify');
Route::post('/documents/{document}/verify', [GuidanceDashboardController::class, 'verifyDocument'])
    ->name('documents.verify');
Route::post('/documents/{document}/unverify', [GuidanceDashboardController::class, 'unverifyDocument'])
    ->name('documents.unverify');
Route::post('/documents/{document}/reject', [GuidanceDashboardController::class, 'rejectDocument'])
    ->name('documents.reject');
Route::get('/documents/{document}/view', [GuidanceDashboardController::class, 'viewDocument'])
    ->name('documents.view');
Route::get('/reports/enrollment', [\App\Http\Controllers\Guidance\EnrollmentReportController::class, 'index'])->name('reports.enrollment');
Route::get('/reports/promotion', [\App\Http\Controllers\Guidance\PromotionReportController::class, 'index'])->name('reports.promotion');
