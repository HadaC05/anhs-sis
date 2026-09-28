<?php

use App\Http\Controllers\Admin\AcademicYearConfigurationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AttendanceConfigurationController;
use App\Http\Controllers\Admin\CurriculumConfigurationController;
use App\Http\Controllers\Admin\DocumentReturnReasonConfigurationController;
use App\Http\Controllers\Admin\GradingTermConfigurationController;
use App\Http\Controllers\Admin\MovementReasonConfigurationController;
use App\Http\Controllers\Admin\SectionConfigurationController;
use App\Http\Controllers\Admin\SubjectConfigurationController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use Illuminate\Support\Facades\Route;

Route::get('/school-information', [\App\Http\Controllers\Admin\SchoolInformationController::class, 'edit'])->name('school-information.edit');
Route::put('/school-information', [\App\Http\Controllers\Admin\SchoolInformationController::class, 'update'])->name('school-information.update');

Route::get('/users', [AdminUserController::class, 'index'])
    ->name('users');
Route::post('/users', [AdminUserController::class, 'store'])
    ->name('users.store');
Route::put('/users/{user}', [AdminUserController::class, 'update'])
    ->name('users.update');
Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])
    ->name('users.toggle-status');

Route::get('/academic-year-configuration', [AcademicYearConfigurationController::class, 'index'])
    ->name('academic-year-config.index');
Route::post('/academic-year-configuration', [AcademicYearConfigurationController::class, 'store'])
    ->name('academic-year-config.store');
Route::put('/academic-year-configuration/{academicYear}', [AcademicYearConfigurationController::class, 'update'])
    ->name('academic-year-config.update');
Route::patch('/academic-year-configuration/{academicYear}/toggle-status', [AcademicYearConfigurationController::class, 'toggleStatus'])
    ->name('academic-year-config.toggle-status');
Route::get('/attendance-configuration', [AttendanceConfigurationController::class, 'index'])
    ->name('attendance-config.index');
Route::put('/attendance-configuration', [AttendanceConfigurationController::class, 'update'])
    ->name('attendance-config.update');
Route::get('/grading-term-configuration', [GradingTermConfigurationController::class, 'index'])
    ->name('grading-term-config.index');
Route::post('/grading-term-configuration', [GradingTermConfigurationController::class, 'store'])
    ->name('grading-term-config.store');
Route::put('/grading-term-configuration/settings', [GradingTermConfigurationController::class, 'updateSettings'])
    ->name('grading-term-config.settings.update');
Route::put('/grading-term-configuration/open-term', [GradingTermConfigurationController::class, 'updateOpenTerm'])
    ->name('grading-term-config.open-term.update');
Route::put('/grading-term-configuration/junior-high/close-all', [GradingTermConfigurationController::class, 'closeAllJuniorHighTerms'])
    ->name('grading-term-config.junior-high.close-all');
Route::put('/grading-term-configuration/senior-high', [GradingTermConfigurationController::class, 'updateSeniorHigh'])
    ->name('grading-term-config.senior-high.update');
Route::put('/grading-term-configuration/senior-high/semester', [GradingTermConfigurationController::class, 'updateSeniorHighSemester'])
    ->name('grading-term-config.senior-high.semester.update');
Route::put('/grading-term-configuration/senior-high/semester/{semester}/close', [GradingTermConfigurationController::class, 'closeSeniorHighSemester'])
    ->name('grading-term-config.senior-high.semester.close');
Route::put('/grading-term-configuration/senior-high/term', [GradingTermConfigurationController::class, 'updateSeniorHighTerm'])
    ->name('grading-term-config.senior-high.term.update');
Route::put('/grading-term-configuration/{term}/junior-high-status', [GradingTermConfigurationController::class, 'updateJuniorHighStatus'])
    ->name('grading-term-config.junior-high-status.update');
Route::put('/grading-term-configuration/{term}/senior-high-status', [GradingTermConfigurationController::class, 'updateSeniorHighStatus'])
    ->name('grading-term-config.senior-high-status.update');
Route::put('/grading-term-configuration/{term}', [GradingTermConfigurationController::class, 'update'])
    ->name('grading-term-config.update');
Route::get('/section-configuration', [SectionConfigurationController::class, 'index'])
    ->name('section-config.index');
Route::post('/section-configuration', [SectionConfigurationController::class, 'store'])
    ->name('section-config.store');
Route::put('/section-configuration/{section}', [SectionConfigurationController::class, 'update'])
    ->name('section-config.update');
Route::patch('/section-configuration/{section}/toggle-status', [SectionConfigurationController::class, 'toggleStatus'])
    ->name('section-config.toggle-status');
Route::get('/movement-reason-configuration', [MovementReasonConfigurationController::class, 'index'])
    ->name('movement-reason-config.index');
Route::post('/movement-reason-configuration', [MovementReasonConfigurationController::class, 'store'])
    ->name('movement-reason-config.store');
Route::put('/movement-reason-configuration/{movementReason}', [MovementReasonConfigurationController::class, 'update'])
    ->name('movement-reason-config.update');
Route::delete('/movement-reason-configuration/{movementReason}', [MovementReasonConfigurationController::class, 'destroy'])
    ->name('movement-reason-config.delete');
Route::get('/document-return-reason-configuration', [DocumentReturnReasonConfigurationController::class, 'index'])
    ->name('document-return-reason-config.index');
Route::post('/document-return-reason-configuration', [DocumentReturnReasonConfigurationController::class, 'store'])
    ->name('document-return-reason-config.store');
Route::put('/document-return-reason-configuration/{documentReturnReason}', [DocumentReturnReasonConfigurationController::class, 'update'])
    ->name('document-return-reason-config.update');
Route::delete('/document-return-reason-configuration/{documentReturnReason}', [DocumentReturnReasonConfigurationController::class, 'destroy'])
    ->name('document-return-reason-config.delete');
Route::get('/curriculum-configuration', [CurriculumConfigurationController::class, 'index'])
    ->name('curriculum-config.index');
Route::post('/curriculum-configuration/curricula', [CurriculumConfigurationController::class, 'storeMasterCurriculum'])
    ->name('curriculum-config.curricula.store');
Route::put('/curriculum-configuration/curricula/{curricula}', [CurriculumConfigurationController::class, 'updateMasterCurriculum'])
    ->name('curriculum-config.curricula.update');
Route::patch('/curriculum-configuration/curricula/{curricula}/toggle-status', [CurriculumConfigurationController::class, 'toggleMasterCurriculumStatus'])
    ->name('curriculum-config.curricula.toggle-status');
Route::get('/curriculum-configuration/curricula/{curricula}/report', [CurriculumConfigurationController::class, 'curriculumReport'])
    ->name('curriculum-config.curricula.report');
Route::post('/curriculum-configuration', [CurriculumConfigurationController::class, 'storeCurriculum'])
    ->name('curriculum-config.store');
Route::put('/curriculum-configuration/{curriculum}', [CurriculumConfigurationController::class, 'updateCurriculum'])
    ->name('curriculum-config.update');
Route::patch('/curriculum-configuration/{curriculum}/toggle-status', [CurriculumConfigurationController::class, 'toggleCurriculumStatus'])
    ->name('curriculum-config.toggle-status');
Route::post('/curriculum-configuration/subjects', [CurriculumConfigurationController::class, 'storeCurriculumSubject'])
    ->name('curriculum-config.subjects.store');
Route::put('/curriculum-configuration/subjects/{curriculumSubject}', [CurriculumConfigurationController::class, 'updateCurriculumSubject'])
    ->name('curriculum-config.subjects.update');
Route::delete('/curriculum-configuration/subjects/{curriculumSubject}', [CurriculumConfigurationController::class, 'destroyCurriculumSubject'])
    ->name('curriculum-config.subjects.delete');
Route::get('/subject-configuration', [SubjectConfigurationController::class, 'index'])
    ->name('subject-config.index');
Route::post('/subject-configuration', [SubjectConfigurationController::class, 'store'])
    ->name('subject-config.store');
Route::put('/subject-configuration/{subject}', [SubjectConfigurationController::class, 'update'])
    ->name('subject-config.update');
Route::delete('/subject-configuration/{subject}', [SubjectConfigurationController::class, 'destroy'])
    ->name('subject-config.delete');
Route::post('/subject-configuration/preferred-courses', [SubjectConfigurationController::class, 'storePreferredCourse'])
    ->name('subject-config.preferred-courses.store');
Route::put('/subject-configuration/preferred-courses/{preferredCourse}', [SubjectConfigurationController::class, 'updatePreferredCourse'])
    ->name('subject-config.preferred-courses.update');
Route::delete('/subject-configuration/preferred-courses/{preferredCourse}', [SubjectConfigurationController::class, 'destroyPreferredCourse'])
    ->name('subject-config.preferred-courses.delete');

Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index'])
    ->name('teacher-assignments.index');
Route::post('/teacher-assignments', [TeacherAssignmentController::class, 'store'])
    ->name('teacher-assignments.store');
Route::post('/teacher-assignments/bulk', [TeacherAssignmentController::class, 'bulkAssign'])
    ->name('teacher-assignments.bulk');
Route::post('/teacher-assignments/advisory', [TeacherAssignmentController::class, 'assignAdvisory'])
    ->name('teacher-assignments.advisory.assign');
Route::put('/teacher-assignments/advisory/{section}', [TeacherAssignmentController::class, 'updateAdvisory'])
    ->name('teacher-assignments.advisory.update');
Route::put('/teacher-assignments/{assignment}', [TeacherAssignmentController::class, 'update'])
    ->name('teacher-assignments.update');
Route::patch('/teacher-assignments/{assignment}/unlock-grades', [TeacherAssignmentController::class, 'unlockGrades'])
    ->name('teacher-assignments.unlock-grades');
Route::delete('/teacher-assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])
    ->name('teacher-assignments.delete');
