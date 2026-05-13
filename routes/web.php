<?php

use App\Http\Controllers\Admin\ApplicationReviewController;
use App\Http\Controllers\Admin\AcademicYearConfigurationController;
use App\Http\Controllers\Admin\BookConfigurationController;
use App\Http\Controllers\Admin\CurriculumConfigurationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\MovementReasonConfigurationController;
use App\Http\Controllers\Admin\SectionConfigurationController;
use App\Http\Controllers\Admin\SubjectConfigurationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ForcePasswordController;
use App\Http\Controllers\Guidance\GuidanceDashboardController;
use App\Http\Controllers\Principal\PrincipalDashboardController;
use App\Http\Controllers\Registrar\RegistrarDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherSectionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : view('landing');
})->name('home');

Route::get('/register', [ApplicationController::class, 'create'])->name('register');
Route::post('/register', [ApplicationController::class, 'store'])->name('register.store');
Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
Route::post('/applications/status', [ApplicationController::class, 'checkStatus'])->name('applications.status');

Route::middleware(['auth'])->group(function () {
    Route::get('/force-password', [ForcePasswordController::class, 'edit'])->name('force-password.edit');
    Route::post('/force-password', [ForcePasswordController::class, 'update'])->name('force-password.update');
});

Route::middleware(['auth', 'verified', 'force_password'])->group(function () {
    Route::get('dashboard', function () {
        $user = Auth::user();

        if ($user && $user->role && $user->role->role_name === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user && $user->role && $user->role->role_name === 'student') {
            return redirect()->route('student.dashboard');
        }

        if ($user && $user->role && $user->role->role_name === 'guidance counselor') {
            return redirect()->route('guidance.dashboard');
        }

        if ($user && $user->role && $user->role->role_name === 'teacher') {
            return redirect()->route('teacher.dashboard');
        }

        if ($user && $user->role && $user->role->role_name === 'registrar') {
            return redirect()->route('registrar.dashboard');
        }

        if ($user && $user->role && $user->role->role_name === 'principal') {
            return redirect()->route('principal.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('admin')
        ->name('admin.dashboard');

    Route::get('/admin/users', [AdminUserController::class, 'index'])
        ->middleware('admin')
        ->name('admin.users');
    Route::post('/admin/users', [AdminUserController::class, 'store'])
        ->middleware('admin')
        ->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])
        ->middleware('admin')
        ->name('admin.users.update');
    Route::patch('/admin/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])
        ->middleware('admin')
        ->name('admin.users.toggle-status');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.users.delete');

    Route::get('/admin/applications', [ApplicationReviewController::class, 'index'])
        ->middleware('admin')
        ->name('admin.applications.index');
    Route::post('/admin/applications/{application}/approve', [ApplicationReviewController::class, 'approve'])
        ->middleware('admin')
        ->name('admin.applications.approve');
    Route::post('/admin/applications/{application}/reject', [ApplicationReviewController::class, 'reject'])
        ->middleware('admin')
        ->name('admin.applications.reject');
    Route::get('/admin/book-configuration', [BookConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.book-config.index');
    Route::post('/admin/book-configuration/books', [BookConfigurationController::class, 'storeBook'])
        ->middleware('admin')
        ->name('admin.book-config.books.store');
    Route::put('/admin/book-configuration/books/{book}', [BookConfigurationController::class, 'updateBook'])
        ->middleware('admin')
        ->name('admin.book-config.books.update');
    Route::delete('/admin/book-configuration/books/{book}', [BookConfigurationController::class, 'destroyBook'])
        ->middleware('admin')
        ->name('admin.book-config.books.delete');
    Route::post('/admin/book-configuration/inventory', [BookConfigurationController::class, 'storeInventory'])
        ->middleware('admin')
        ->name('admin.book-config.inventory.store');
    Route::put('/admin/book-configuration/inventory/{inventory}', [BookConfigurationController::class, 'updateInventory'])
        ->middleware('admin')
        ->name('admin.book-config.inventory.update');
    Route::delete('/admin/book-configuration/inventory/{inventory}', [BookConfigurationController::class, 'destroyInventory'])
        ->middleware('admin')
        ->name('admin.book-config.inventory.delete');
    Route::get('/admin/academic-year-configuration', [AcademicYearConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.academic-year-config.index');
    Route::post('/admin/academic-year-configuration', [AcademicYearConfigurationController::class, 'store'])
        ->middleware('admin')
        ->name('admin.academic-year-config.store');
    Route::put('/admin/academic-year-configuration/{academicYear}', [AcademicYearConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.academic-year-config.update');
    Route::patch('/admin/academic-year-configuration/{academicYear}/toggle-status', [AcademicYearConfigurationController::class, 'toggleStatus'])
        ->middleware('admin')
        ->name('admin.academic-year-config.toggle-status');
    Route::get('/admin/section-configuration', [SectionConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.section-config.index');
    Route::post('/admin/section-configuration', [SectionConfigurationController::class, 'store'])
        ->middleware('admin')
        ->name('admin.section-config.store');
    Route::put('/admin/section-configuration/{section}', [SectionConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.section-config.update');
    Route::delete('/admin/section-configuration/{section}', [SectionConfigurationController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.section-config.delete');
    Route::get('/admin/movement-reason-configuration', [MovementReasonConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.movement-reason-config.index');
    Route::post('/admin/movement-reason-configuration', [MovementReasonConfigurationController::class, 'store'])
        ->middleware('admin')
        ->name('admin.movement-reason-config.store');
    Route::put('/admin/movement-reason-configuration/{movementReason}', [MovementReasonConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.movement-reason-config.update');
    Route::delete('/admin/movement-reason-configuration/{movementReason}', [MovementReasonConfigurationController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.movement-reason-config.delete');
    Route::get('/admin/curriculum-configuration', [CurriculumConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.curriculum-config.index');
    Route::post('/admin/curriculum-configuration', [CurriculumConfigurationController::class, 'storeCurriculum'])
        ->middleware('admin')
        ->name('admin.curriculum-config.store');
    Route::put('/admin/curriculum-configuration/{curriculum}', [CurriculumConfigurationController::class, 'updateCurriculum'])
        ->middleware('admin')
        ->name('admin.curriculum-config.update');
    Route::patch('/admin/curriculum-configuration/{curriculum}/toggle-status', [CurriculumConfigurationController::class, 'toggleCurriculumStatus'])
        ->middleware('admin')
        ->name('admin.curriculum-config.toggle-status');
    Route::post('/admin/curriculum-configuration/subjects', [CurriculumConfigurationController::class, 'storeCurriculumSubject'])
        ->middleware('admin')
        ->name('admin.curriculum-config.subjects.store');
    Route::put('/admin/curriculum-configuration/subjects/{curriculumSubject}', [CurriculumConfigurationController::class, 'updateCurriculumSubject'])
        ->middleware('admin')
        ->name('admin.curriculum-config.subjects.update');
    Route::delete('/admin/curriculum-configuration/subjects/{curriculumSubject}', [CurriculumConfigurationController::class, 'destroyCurriculumSubject'])
        ->middleware('admin')
        ->name('admin.curriculum-config.subjects.delete');
    Route::get('/admin/subject-configuration', [SubjectConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.subject-config.index');
    Route::post('/admin/subject-configuration', [SubjectConfigurationController::class, 'store'])
        ->middleware('admin')
        ->name('admin.subject-config.store');
    Route::put('/admin/subject-configuration/{subject}', [SubjectConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.subject-config.update');
    Route::delete('/admin/subject-configuration/{subject}', [SubjectConfigurationController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.subject-config.delete');
    Route::post('/admin/subject-configuration/preferred-courses', [SubjectConfigurationController::class, 'storePreferredCourse'])
        ->middleware('admin')
        ->name('admin.subject-config.preferred-courses.store');
    Route::put('/admin/subject-configuration/preferred-courses/{preferredCourse}', [SubjectConfigurationController::class, 'updatePreferredCourse'])
        ->middleware('admin')
        ->name('admin.subject-config.preferred-courses.update');
    Route::delete('/admin/subject-configuration/preferred-courses/{preferredCourse}', [SubjectConfigurationController::class, 'destroyPreferredCourse'])
        ->middleware('admin')
        ->name('admin.subject-config.preferred-courses.delete');

    Route::get('/admin/teacher-assignments', [TeacherAssignmentController::class, 'index'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.index');
    Route::post('/admin/teacher-assignments', [TeacherAssignmentController::class, 'store'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.store');
    Route::post('/admin/teacher-assignments/bulk', [TeacherAssignmentController::class, 'bulkAssign'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.bulk');
    Route::put('/admin/teacher-assignments/{assignment}', [TeacherAssignmentController::class, 'update'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.update');
    Route::delete('/admin/teacher-assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.delete');

    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
        ->middleware('student')
        ->name('student.dashboard');
    Route::get('/student/enrollment', [StudentDashboardController::class, 'enrollment'])
        ->middleware('student')
        ->name('student.enrollment');
    Route::get('/student/profile', [StudentDashboardController::class, 'profile'])
        ->middleware('student')
        ->name('student.profile');
    Route::post('/student/enrollment', [StudentDashboardController::class, 'storeEnrollment'])
        ->middleware('student')
        ->name('student.enrollment.store');
    Route::get('/student/documents', [StudentDashboardController::class, 'documents'])
        ->middleware('student')
        ->name('student.documents');
    Route::get('/student/documents/{document}/view', [StudentDashboardController::class, 'viewDocument'])
        ->middleware('student')
        ->name('student.documents.view');
    Route::post('/student/documents/upload', [StudentDashboardController::class, 'uploadDocument'])
        ->middleware('student')
        ->name('student.documents.upload');
    Route::delete('/student/documents/{document}', [StudentDashboardController::class, 'deleteDocument'])
        ->middleware('student')
        ->name('student.documents.delete');
    Route::get('/student/grades', [StudentDashboardController::class, 'grades'])
        ->middleware('student')
        ->name('student.grades');

    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])
        ->middleware('teacher')
        ->name('teacher.dashboard');
    Route::get('/teacher/sections', [TeacherSectionController::class, 'index'])
        ->middleware('teacher')
        ->name('teacher.sections.index');
    Route::get('/teacher/sections/{assignment}', [TeacherSectionController::class, 'show'])
        ->middleware('teacher')
        ->name('teacher.sections.show');
    Route::post('/teacher/sections/{assignment}/grades', [TeacherSectionController::class, 'storeGrades'])
        ->middleware('teacher')
        ->name('teacher.sections.grades.store');
    Route::post('/teacher/sections/{assignment}/grades/submit', [TeacherSectionController::class, 'submitGrades'])
        ->middleware('teacher')
        ->name('teacher.sections.grades.submit');
    Route::get('/teacher/sections/{assignment}/summary/print', [TeacherSectionController::class, 'summaryPrint'])
        ->middleware('teacher')
        ->name('teacher.sections.summary.print');

    Route::get('/registrar/dashboard', [RegistrarDashboardController::class, 'index'])
        ->name('registrar.dashboard');
    Route::get('/registrar/students', [RegistrarDashboardController::class, 'students'])
        ->name('registrar.students');
    Route::get('/registrar/students/{student}', [RegistrarDashboardController::class, 'show'])
        ->middleware('registrar')
        ->name('registrar.students.show');
    Route::get('/registrar/grade-approvals', [RegistrarDashboardController::class, 'gradeApprovals'])
        ->name('registrar.grade-approvals');
    Route::post('/registrar/grade-approvals/{assignment}/approve', [RegistrarDashboardController::class, 'approveGrades'])
        ->name('registrar.grade-approvals.approve');
    Route::post('/registrar/grade-approvals/{assignment}/reject', [RegistrarDashboardController::class, 'rejectGrades'])
        ->name('registrar.grade-approvals.reject');

    Route::get('/principal/dashboard', [PrincipalDashboardController::class, 'index'])
        ->middleware('principal')
        ->name('principal.dashboard');

    Route::get('/guidance/dashboard', [GuidanceDashboardController::class, 'index'])
        ->middleware('guidance')
        ->name('guidance.dashboard');
    Route::get('/guidance/enrollments', [GuidanceDashboardController::class, 'enrollments'])
        ->middleware('guidance')
        ->name('guidance.enrollments.index');
    Route::get('/guidance/enrollments/{enrollment}', [GuidanceDashboardController::class, 'show'])
        ->middleware('guidance')
        ->name('guidance.enrollments.show');
    Route::post('/guidance/enrollments/{enrollment}/approve', [GuidanceDashboardController::class, 'approve'])
        ->middleware('guidance')
        ->name('guidance.enrollments.approve');
    Route::post('/guidance/enrollments/bulk-approve', [GuidanceDashboardController::class, 'bulkApprove'])
        ->middleware('guidance')
        ->name('guidance.enrollments.bulk-approve');
    Route::get('/guidance/enrollments/{enrollment}/print', [GuidanceDashboardController::class, 'print'])
        ->middleware('guidance')
        ->name('guidance.enrollments.print');
    Route::post('/guidance/enrollments/print', [GuidanceDashboardController::class, 'printMultiple'])
        ->middleware('guidance')
        ->name('guidance.enrollments.print-multiple');
    Route::post('/guidance/documents/{document}/verify', [GuidanceDashboardController::class, 'verifyDocument'])
        ->middleware('guidance')
        ->name('guidance.documents.verify');
    Route::post('/guidance/documents/{document}/reject', [GuidanceDashboardController::class, 'rejectDocument'])
        ->middleware('guidance')
        ->name('guidance.documents.reject');
    Route::get('/guidance/documents/{document}/view', [GuidanceDashboardController::class, 'viewDocument'])
        ->middleware('guidance')
        ->name('guidance.documents.view');
    Route::get('/guidance/sections', [GuidanceDashboardController::class, 'sectionsIndex'])
        ->middleware('guidance')
        ->name('guidance.sections.index');
    Route::get('/guidance/sections/master-list', [GuidanceDashboardController::class, 'sectionsMasterList'])
        ->middleware('guidance')
        ->name('guidance.sections.master-list');
    Route::get('/guidance/sections/{section}', [GuidanceDashboardController::class, 'sectionsShow'])
        ->middleware('guidance')
        ->name('guidance.sections.show');
    Route::get('/guidance/sections/{section}/class-list', [GuidanceDashboardController::class, 'sectionsClassList'])
        ->middleware('guidance')
        ->name('guidance.sections.class-list');
    Route::get('/guidance/sections/{section}/class-list/pdf', [GuidanceDashboardController::class, 'sectionsClassList'])
        ->middleware('guidance')
        ->name('guidance.sections.class-list.pdf');
    Route::get('/guidance/sections/master-list/pdf', [GuidanceDashboardController::class, 'sectionsMasterList'])
        ->middleware('guidance')
        ->name('guidance.sections.master-list.pdf');
    Route::get('/guidance/sectioning', [GuidanceDashboardController::class, 'sectioning'])
        ->middleware('guidance')
        ->name('guidance.sectioning');
});

require __DIR__.'/settings.php';
