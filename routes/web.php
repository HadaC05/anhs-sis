<?php

use App\Http\Controllers\Admin\AcademicYearConfigurationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AttendanceConfigurationController;
use App\Http\Controllers\Admin\CurriculumConfigurationController;
use App\Http\Controllers\Admin\DocumentReturnReasonConfigurationController;
use App\Http\Controllers\Admin\GradingTermConfigurationController;
use App\Http\Controllers\Admin\MovementReasonConfigurationController;
use App\Http\Controllers\Admin\SectionConfigurationController;
use App\Http\Controllers\Admin\SubjectConfigurationController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\ForcePasswordController;
use App\Http\Controllers\Guidance\GuidanceDashboardController;
use App\Http\Controllers\Guidance\GuidanceEnrollmentController;
use App\Http\Controllers\Principal\PrincipalDashboardController;
use App\Http\Controllers\Registrar\RegistrarDashboardController;
use App\Http\Controllers\Student\StudentAccountController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherSectionController;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : view('landing');
})->name('home');

Route::get('/register', [ApplicationController::class, 'create'])->name('register');
Route::get('/register/check-lrn', [ApplicationController::class, 'checkLrn'])->name('register.check-lrn');
Route::get('/register/check-email', [ApplicationController::class, 'checkEmail'])->name('register.check-email');
Route::post('/register', [ApplicationController::class, 'store'])->name('register.store');
Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
Route::post('/applications/status', [ApplicationController::class, 'checkStatus'])->name('applications.status');
Route::post('/forgot-password/send-otp', [PasswordResetOtpController::class, 'send'])->name('password.otp.send');
Route::post('/forgot-password/verify-otp', [PasswordResetOtpController::class, 'verify'])->name('password.otp.verify');

Route::middleware(['auth'])->group(function () {
    Route::get('/force-password', [ForcePasswordController::class, 'edit'])->name('force-password.edit');
    Route::post('/force-password', [ForcePasswordController::class, 'update'])->name('force-password.update');
});

Route::middleware(['auth', 'verified', 'force_password'])->group(function () {
    Route::get('dashboard', function () {
        $user = Auth::user();

        if ($user instanceof Student) {
            return redirect()->route('student.dashboard');
        }

        return match ($user?->roleName()) {
            'admin' => redirect()->route('admin.dashboard'),
            'guidance counselor' => redirect()->route('guidance.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'registrar' => redirect()->route('registrar.dashboard'),
            'principal' => redirect()->route('principal.dashboard'),
            default => view('dashboard'),
        };
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
    Route::get('/admin/attendance-configuration', [AttendanceConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.attendance-config.index');
    Route::put('/admin/attendance-configuration', [AttendanceConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.attendance-config.update');
    Route::get('/admin/grading-term-configuration', [GradingTermConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.grading-term-config.index');
    Route::post('/admin/grading-term-configuration', [GradingTermConfigurationController::class, 'store'])
        ->middleware('admin')
        ->name('admin.grading-term-config.store');
    Route::put('/admin/grading-term-configuration/settings', [GradingTermConfigurationController::class, 'updateSettings'])
        ->middleware('admin')
        ->name('admin.grading-term-config.settings.update');
    Route::put('/admin/grading-term-configuration/open-term', [GradingTermConfigurationController::class, 'updateOpenTerm'])
        ->middleware('admin')
        ->name('admin.grading-term-config.open-term.update');
    Route::put('/admin/grading-term-configuration/junior-high/close-all', [GradingTermConfigurationController::class, 'closeAllJuniorHighTerms'])
        ->middleware('admin')
        ->name('admin.grading-term-config.junior-high.close-all');
    Route::put('/admin/grading-term-configuration/senior-high', [GradingTermConfigurationController::class, 'updateSeniorHigh'])
        ->middleware('admin')
        ->name('admin.grading-term-config.senior-high.update');
    Route::put('/admin/grading-term-configuration/senior-high/semester', [GradingTermConfigurationController::class, 'updateSeniorHighSemester'])
        ->middleware('admin')
        ->name('admin.grading-term-config.senior-high.semester.update');
    Route::put('/admin/grading-term-configuration/senior-high/semester/{semester}/close', [GradingTermConfigurationController::class, 'closeSeniorHighSemester'])
        ->middleware('admin')
        ->name('admin.grading-term-config.senior-high.semester.close');
    Route::put('/admin/grading-term-configuration/senior-high/term', [GradingTermConfigurationController::class, 'updateSeniorHighTerm'])
        ->middleware('admin')
        ->name('admin.grading-term-config.senior-high.term.update');
    Route::put('/admin/grading-term-configuration/{term}/junior-high-status', [GradingTermConfigurationController::class, 'updateJuniorHighStatus'])
        ->middleware('admin')
        ->name('admin.grading-term-config.junior-high-status.update');
    Route::put('/admin/grading-term-configuration/{term}/senior-high-status', [GradingTermConfigurationController::class, 'updateSeniorHighStatus'])
        ->middleware('admin')
        ->name('admin.grading-term-config.senior-high-status.update');
    Route::put('/admin/grading-term-configuration/{term}', [GradingTermConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.grading-term-config.update');
    Route::get('/admin/section-configuration', [SectionConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.section-config.index');
    Route::post('/admin/section-configuration', [SectionConfigurationController::class, 'store'])
        ->middleware('admin')
        ->name('admin.section-config.store');
    Route::put('/admin/section-configuration/{section}', [SectionConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.section-config.update');
    Route::patch('/admin/section-configuration/{section}/toggle-status', [SectionConfigurationController::class, 'toggleStatus'])
        ->middleware('admin')
        ->name('admin.section-config.toggle-status');
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
    Route::get('/admin/document-return-reason-configuration', [DocumentReturnReasonConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.document-return-reason-config.index');
    Route::post('/admin/document-return-reason-configuration', [DocumentReturnReasonConfigurationController::class, 'store'])
        ->middleware('admin')
        ->name('admin.document-return-reason-config.store');
    Route::put('/admin/document-return-reason-configuration/{documentReturnReason}', [DocumentReturnReasonConfigurationController::class, 'update'])
        ->middleware('admin')
        ->name('admin.document-return-reason-config.update');
    Route::delete('/admin/document-return-reason-configuration/{documentReturnReason}', [DocumentReturnReasonConfigurationController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.document-return-reason-config.delete');
    Route::get('/admin/curriculum-configuration', [CurriculumConfigurationController::class, 'index'])
        ->middleware('admin')
        ->name('admin.curriculum-config.index');
    Route::post('/admin/curriculum-configuration/curricula', [CurriculumConfigurationController::class, 'storeMasterCurriculum'])
        ->middleware('admin')
        ->name('admin.curriculum-config.curricula.store');
    Route::put('/admin/curriculum-configuration/curricula/{curricula}', [CurriculumConfigurationController::class, 'updateMasterCurriculum'])
        ->middleware('admin')
        ->name('admin.curriculum-config.curricula.update');
    Route::patch('/admin/curriculum-configuration/curricula/{curricula}/toggle-status', [CurriculumConfigurationController::class, 'toggleMasterCurriculumStatus'])
        ->middleware('admin')
        ->name('admin.curriculum-config.curricula.toggle-status');
    Route::get('/admin/curriculum-configuration/curricula/{curricula}/report', [CurriculumConfigurationController::class, 'curriculumReport'])
        ->middleware('admin')
        ->name('admin.curriculum-config.curricula.report');
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
    Route::post('/admin/teacher-assignments/advisory', [TeacherAssignmentController::class, 'assignAdvisory'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.advisory.assign');
    Route::put('/admin/teacher-assignments/advisory/{section}', [TeacherAssignmentController::class, 'updateAdvisory'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.advisory.update');
    Route::put('/admin/teacher-assignments/{assignment}', [TeacherAssignmentController::class, 'update'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.update');
    Route::patch('/admin/teacher-assignments/{assignment}/unlock-grades', [TeacherAssignmentController::class, 'unlockGrades'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.unlock-grades');
    Route::delete('/admin/teacher-assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])
        ->middleware('admin')
        ->name('admin.teacher-assignments.delete');

    Route::get('/student/dashboard', [StudentDashboardController::class, 'index'])
        ->middleware('student')
        ->name('student.dashboard');
    Route::get('/student/profile', [StudentDashboardController::class, 'profile'])
        ->middleware('student')
        ->name('student.profile');
    Route::put('/student/profile', [StudentDashboardController::class, 'updateProfile'])
        ->middleware('student')
        ->name('student.profile.update');
    Route::get('/student/account', [StudentAccountController::class, 'edit'])
        ->middleware('student')
        ->name('student.account');
    Route::put('/student/account', [StudentAccountController::class, 'update'])
        ->middleware('student')
        ->name('student.account.update');
    Route::put('/student/account/password', [StudentAccountController::class, 'updatePassword'])
        ->middleware('student')
        ->name('student.account.password');
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
    Route::get('/student/subjects', [StudentDashboardController::class, 'subjects'])
        ->middleware('student')
        ->name('student.subjects');

    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])
        ->middleware('teacher')
        ->name('teacher.dashboard');
    Route::get('/teacher/sections', [TeacherSectionController::class, 'index'])
        ->middleware('teacher')
        ->name('teacher.sections.index');
    Route::get('/teacher/advisory', [TeacherSectionController::class, 'advisoryIndex'])
        ->middleware('teacher')
        ->name('teacher.advisory.index');
    Route::get('/teacher/advisory/{section}/observed-values', [TeacherSectionController::class, 'advisoryObservedValues'])
        ->middleware('teacher')
        ->name('teacher.advisory.observed-values');
    Route::get('/teacher/advisory/{section}/promotions', [TeacherSectionController::class, 'advisoryPromotions'])
        ->middleware('teacher')
        ->name('teacher.advisory.promotions.index');
    Route::get('/teacher/advisory/{section}/attendance', [TeacherSectionController::class, 'advisoryAttendance'])
        ->middleware('teacher')
        ->name('teacher.advisory.attendance');
    Route::post('/teacher/advisory/{section}/attendance', [TeacherSectionController::class, 'storeAdvisoryAttendance'])
        ->middleware('teacher')
        ->name('teacher.advisory.attendance.store');
    Route::post('/teacher/advisory/{section}/attendance/sf2', [TeacherSectionController::class, 'uploadAdvisorySf2'])
        ->middleware('teacher')
        ->name('teacher.advisory.attendance.sf2');
    Route::post('/teacher/advisory/{section}/observed-values', [TeacherSectionController::class, 'storeAdvisoryObservedValues'])
        ->middleware('teacher')
        ->name('teacher.advisory.observed-values.store');
    Route::post('/teacher/advisory/{section}/observed-values/bulk', [TeacherSectionController::class, 'bulkStoreAdvisoryObservedValues'])
        ->middleware('teacher')
        ->name('teacher.advisory.observed-values.bulk');
    Route::post('/teacher/advisory/{section}/observed-values/submit', [TeacherSectionController::class, 'submitAdvisoryObservedValues'])
        ->middleware('teacher')
        ->name('teacher.advisory.observed-values.submit');
    Route::match(['get', 'post'], '/teacher/advisory/{section}/sf9', [TeacherSectionController::class, 'advisorySf9'])
        ->middleware('teacher')
        ->name('teacher.advisory.sf9');
    Route::match(['get', 'post'], '/teacher/advisory/{section}/sf10', [TeacherSectionController::class, 'advisorySf10'])
        ->middleware('teacher')
        ->name('teacher.advisory.sf10');
    Route::post('/teacher/advisory/{section}/class-list/import', [TeacherSectionController::class, 'importAdvisoryClassList'])
        ->middleware('teacher')
        ->name('teacher.advisory.class-list.import');
    Route::get('/teacher/advisory/{section}/students/{enrollment}/profile', [TeacherSectionController::class, 'advisoryStudentProfile'])
        ->middleware('teacher')
        ->name('teacher.advisory.students.profile');
    Route::get('/teacher/advisory/{section}/class-list', [TeacherSectionController::class, 'advisoryClassList'])
        ->middleware('teacher')
        ->name('teacher.advisory.class-list.index');
    Route::get('/teacher/advisory/{section}', [TeacherSectionController::class, 'advisoryShow'])
        ->middleware('teacher')
        ->name('teacher.advisory.show');
    Route::post('/teacher/advisory/{section}/promotions/bulk', [TeacherSectionController::class, 'bulkPromote'])
        ->middleware('teacher')
        ->name('teacher.advisory.promotions.bulk');
    Route::post('/teacher/advisory/{section}/promotions/{enrollment}', [TeacherSectionController::class, 'promote'])
        ->middleware('teacher')
        ->name('teacher.advisory.promotions.promote');
    Route::get('/teacher/sections/{assignment}', [TeacherSectionController::class, 'show'])
        ->middleware('teacher')
        ->name('teacher.sections.show');
    Route::post('/teacher/sections/{assignment}/class-list/import', [TeacherSectionController::class, 'importClassList'])
        ->middleware('teacher')
        ->name('teacher.sections.class-list.import');
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
        ->middleware('registrar')
        ->name('registrar.dashboard');
    Route::get('/registrar/students', [RegistrarDashboardController::class, 'students'])
        ->middleware('registrar')
        ->name('registrar.students');
    Route::get('/registrar/students/{student}', [RegistrarDashboardController::class, 'show'])
        ->middleware('registrar')
        ->name('registrar.students.show');
    Route::get('/registrar/students/{student}/enrollments/{enrollment}/sf9', [RegistrarDashboardController::class, 'studentSf9'])
        ->middleware('registrar')
        ->name('registrar.students.sf9');
    Route::get('/registrar/students/{student}/sf10', [RegistrarDashboardController::class, 'studentSf10'])
        ->middleware('registrar')
        ->name('registrar.students.sf10');
    Route::get('/registrar/grade-approvals', [RegistrarDashboardController::class, 'gradeApprovals'])
        ->middleware('registrar')
        ->name('registrar.grade-approvals');
    Route::get('/registrar/grade-approvals/{assignment}', [RegistrarDashboardController::class, 'showGradeApproval'])
        ->middleware('registrar')
        ->name('registrar.grade-approvals.show');
    Route::post('/registrar/grade-approvals/{assignment}/approve', [RegistrarDashboardController::class, 'approveGrades'])
        ->middleware('registrar')
        ->name('registrar.grade-approvals.approve');
    Route::post('/registrar/grade-approvals/{assignment}/reject', [RegistrarDashboardController::class, 'rejectGrades'])
        ->middleware('registrar')
        ->name('registrar.grade-approvals.reject');
    Route::get('/registrar/class-subjects', [RegistrarDashboardController::class, 'classSubjects'])
        ->middleware('registrar')
        ->name('registrar.class-subjects.index');
    Route::get('/registrar/teacher-assignments', [RegistrarDashboardController::class, 'teacherAssignments'])
        ->middleware('registrar')
        ->name('registrar.teacher-assignments');
    Route::get('/registrar/classes/{section}', [RegistrarDashboardController::class, 'classStatus'])
        ->middleware('registrar')
        ->name('registrar.classes.status');
    Route::get('/registrar/class-subjects/{assignment}', [RegistrarDashboardController::class, 'showClassSubject'])
        ->middleware('registrar')
        ->name('registrar.class-subjects.show');
    Route::post('/registrar/class-subjects/{assignment}/unlock-term', [RegistrarDashboardController::class, 'unlockClassSubjectTerm'])
        ->middleware('registrar')
        ->name('registrar.class-subjects.unlock-term');

    Route::get('/principal/dashboard', [PrincipalDashboardController::class, 'index'])
        ->middleware('principal')
        ->name('principal.dashboard');
    Route::get('/principal/grade-releases', [PrincipalDashboardController::class, 'gradeReleases'])
        ->middleware('principal')
        ->name('principal.grade-releases');
    Route::get('/principal/proficiency-levels', [PrincipalDashboardController::class, 'proficiencyLevels'])
        ->middleware('principal')
        ->name('principal.proficiency-levels');
    Route::get('/principal/reports/age-for-grade', [PrincipalDashboardController::class, 'ageForGradeReport'])
        ->middleware('principal')
        ->name('principal.reports.age-for-grade');
    Route::get('/principal/grade-releases/{assignment}', [PrincipalDashboardController::class, 'showGradeRelease'])
        ->middleware('principal')
        ->name('principal.grade-releases.show');
    Route::post('/principal/grade-releases/{assignment}/release', [PrincipalDashboardController::class, 'releaseGrades'])
        ->middleware('principal')
        ->name('principal.grade-releases.release');
    Route::post('/principal/grade-releases/bulk-release', [PrincipalDashboardController::class, 'bulkReleaseGrades'])
        ->middleware('principal')
        ->name('principal.grade-releases.bulk-release');

    Route::get('/guidance/dashboard', [GuidanceDashboardController::class, 'index'])
        ->middleware('guidance')
        ->name('guidance.dashboard');
    Route::get('/guidance/enrollments', [GuidanceDashboardController::class, 'enrollments'])
        ->middleware('guidance')
        ->name('guidance.enrollments.index');
    Route::get('/guidance/promotions', [GuidanceDashboardController::class, 'promotions'])
        ->middleware('guidance')
        ->name('guidance.promotions.index');
    Route::post('/guidance/promotions/{enrollment}/confirm', [GuidanceDashboardController::class, 'confirmPromotion'])
        ->middleware('guidance')
        ->name('guidance.promotions.confirm');
    Route::get('/guidance/enrollments/create', [GuidanceEnrollmentController::class, 'create'])
        ->middleware('guidance')
        ->name('guidance.enrollments.create');
    Route::post('/guidance/enrollments', [GuidanceEnrollmentController::class, 'store'])
        ->middleware('guidance')
        ->name('guidance.enrollments.store');
    Route::get('/guidance/enrollments/check-lrn', [GuidanceEnrollmentController::class, 'checkLrn'])
        ->middleware('guidance')
        ->name('guidance.enrollments.check-lrn');
    Route::get('/guidance/enrollments/check-email', [GuidanceEnrollmentController::class, 'checkEmail'])
        ->middleware('guidance')
        ->name('guidance.enrollments.check-email');
    Route::get('/guidance/enrollments/{enrollment}/edit', [GuidanceEnrollmentController::class, 'edit'])
        ->middleware('guidance')
        ->name('guidance.enrollments.edit');
    Route::put('/guidance/enrollments/{enrollment}', [GuidanceEnrollmentController::class, 'update'])
        ->middleware('guidance')
        ->name('guidance.enrollments.update');
    Route::get('/guidance/enrollments/{enrollment}', [GuidanceDashboardController::class, 'show'])
        ->middleware('guidance')
        ->name('guidance.enrollments.show');
    Route::patch('/guidance/enrollments/{enrollment}/placement-test', [GuidanceDashboardController::class, 'updatePlacementTestRecommendation'])
        ->middleware('guidance')
        ->name('guidance.enrollments.placement-test');
    Route::post('/guidance/enrollments/{enrollment}/approve', [GuidanceDashboardController::class, 'approve'])
        ->middleware('guidance')
        ->name('guidance.enrollments.approve');
    Route::post('/guidance/enrollments/{enrollment}/confirm', [GuidanceDashboardController::class, 'confirmEnrollment'])
        ->middleware('guidance')
        ->name('guidance.enrollments.confirm');
    Route::patch('/guidance/enrollments/{enrollment}/status', [GuidanceDashboardController::class, 'updateStatus'])
        ->middleware('guidance')
        ->name('guidance.enrollments.status');
    Route::post('/guidance/enrollments/bulk-approve', [GuidanceDashboardController::class, 'bulkApprove'])
        ->middleware('guidance')
        ->name('guidance.enrollments.bulk-approve');
    Route::get('/guidance/enrollments/{enrollment}/print', [GuidanceDashboardController::class, 'print'])
        ->middleware('guidance')
        ->name('guidance.enrollments.print');
    Route::post('/guidance/enrollments/print', [GuidanceDashboardController::class, 'printMultiple'])
        ->middleware('guidance')
        ->name('guidance.enrollments.print-multiple');
    Route::post('/guidance/documents/bulk-verify', [GuidanceDashboardController::class, 'bulkVerifyDocuments'])
        ->middleware('guidance')
        ->name('guidance.documents.bulk-verify');
    Route::post('/guidance/documents/{document}/verify', [GuidanceDashboardController::class, 'verifyDocument'])
        ->middleware('guidance')
        ->name('guidance.documents.verify');
    Route::post('/guidance/documents/{document}/unverify', [GuidanceDashboardController::class, 'unverifyDocument'])
        ->middleware('guidance')
        ->name('guidance.documents.unverify');
    Route::post('/guidance/documents/{document}/reject', [GuidanceDashboardController::class, 'rejectDocument'])
        ->middleware('guidance')
        ->name('guidance.documents.reject');
    Route::get('/guidance/documents/{document}/view', [GuidanceDashboardController::class, 'viewDocument'])
        ->middleware('guidance')
        ->name('guidance.documents.view');
    Route::get('/guidance/reports/age-for-grade', [GuidanceDashboardController::class, 'ageForGradeReport'])
        ->middleware('guidance')
        ->name('guidance.reports.age-for-grade');
    Route::get('/guidance/reports/placement-test-recommendations/download', [GuidanceDashboardController::class, 'downloadPlacementTestRecommendations'])
        ->middleware('guidance')
        ->name('guidance.reports.placement-test-recommendations.download');
    Route::get('/guidance/sections', [GuidanceDashboardController::class, 'sectionsIndex'])
        ->middleware('guidance')
        ->name('guidance.sections.index');
    Route::post('/guidance/sections', [GuidanceDashboardController::class, 'storeSection'])
        ->middleware('guidance')
        ->name('guidance.sections.store');
    Route::get('/guidance/sections/master-list', [GuidanceDashboardController::class, 'sectionsMasterList'])
        ->middleware('guidance')
        ->name('guidance.sections.master-list');
    Route::get('/guidance/sections/{section}', [GuidanceDashboardController::class, 'sectionsShow'])
        ->middleware('guidance')
        ->name('guidance.sections.show');
    Route::patch('/guidance/sections/{section}/capacity', [GuidanceDashboardController::class, 'updateSectionCapacity'])
        ->middleware('guidance')
        ->name('guidance.sections.capacity.update');
    Route::patch('/guidance/sections/{section}', [GuidanceDashboardController::class, 'updateSection'])
        ->middleware('guidance')
        ->name('guidance.sections.update');
    Route::post('/guidance/sections/{section}/transfer', [GuidanceDashboardController::class, 'transferSectionStudents'])
        ->middleware('guidance')
        ->name('guidance.sections.transfer');
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
