<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\ForcePasswordController;
use App\Http\Controllers\Guidance\GuidanceDashboardController;
use App\Http\Controllers\Guidance\GuidanceEnrollmentController;
use App\Http\Controllers\Principal\PrincipalDashboardController;
use App\Http\Controllers\Registrar\RegistrarDashboardController;
use App\Http\Controllers\StaffAccountController;
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
    Route::get('/staff/account', [StaffAccountController::class, 'edit'])->name('staff.account');
    Route::put('/staff/account', [StaffAccountController::class, 'update'])->name('staff.account.update');
    Route::put('/staff/account/password', [StaffAccountController::class, 'updatePassword'])->name('staff.account.password');

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

    // Share management actions while keeping each portal role-protected.
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(__DIR__.'/management.php');
    Route::middleware('principal')->prefix('principal/manage')->name('principal.')->group(__DIR__.'/management.php');

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
    Route::get('/teacher/advisory/{section}/at-risk', [\App\Http\Controllers\Teacher\AdvisoryRiskController::class, 'index'])
        ->middleware('teacher')->name('teacher.advisory.at-risk');
    Route::post('/teacher/advisory/{section}/at-risk/{enrollment}/notify', [\App\Http\Controllers\Teacher\AdvisoryRiskController::class, 'notify'])
        ->middleware('teacher')->name('teacher.advisory.at-risk.notify');
    Route::get('/teacher/advisory/{section}/observed-values', [TeacherSectionController::class, 'advisoryObservedValues'])
        ->middleware('teacher')
        ->name('teacher.advisory.observed-values');
    Route::get('/teacher/advisory/{section}/promotions', [TeacherSectionController::class, 'advisoryPromotions'])
        ->middleware('teacher')
        ->name('teacher.advisory.promotions.index');
    Route::post('/teacher/advisory/{section}/promotions/sf5', [TeacherSectionController::class, 'downloadSf5'])
        ->middleware('teacher')
        ->name('teacher.advisory.promotions.sf5');
    Route::get('/teacher/advisory/{section}/attendance', [TeacherSectionController::class, 'advisoryAttendance'])
        ->middleware('teacher')
        ->name('teacher.advisory.attendance');
    Route::post('/teacher/advisory/{section}/attendance', [TeacherSectionController::class, 'storeAdvisoryAttendance'])
        ->middleware('teacher')
        ->name('teacher.advisory.attendance.store');
    Route::post('/teacher/advisory/{section}/attendance/sf2', [TeacherSectionController::class, 'uploadAdvisorySf2'])
        ->middleware('teacher')
        ->name('teacher.advisory.attendance.sf2');
    Route::get('/teacher/advisory/{section}/attendance/sf2/{upload}', [TeacherSectionController::class, 'viewAdvisorySf2'])
        ->middleware('teacher')
        ->name('teacher.advisory.attendance.sf2.view');
    Route::post('/teacher/advisory/{section}/observed-values', [TeacherSectionController::class, 'storeAdvisoryObservedValues'])
        ->middleware('teacher')
        ->name('teacher.advisory.observed-values.store');
    Route::post('/teacher/advisory/{section}/observed-values/bulk', [TeacherSectionController::class, 'bulkStoreAdvisoryObservedValues'])
        ->middleware('teacher')
        ->name('teacher.advisory.observed-values.bulk');
    Route::match(['get', 'post'], '/teacher/advisory/{section}/sf9', [TeacherSectionController::class, 'advisorySf9'])
        ->middleware('teacher')
        ->name('teacher.advisory.sf9');
    Route::match(['get', 'post'], '/teacher/advisory/{section}/sf10', [TeacherSectionController::class, 'advisorySf10'])
        ->middleware('teacher')
        ->name('teacher.advisory.sf10');
    Route::post('/teacher/advisory/{section}/class-list/import', [TeacherSectionController::class, 'importAdvisoryClassList'])
        ->middleware('teacher')
        ->name('teacher.advisory.class-list.import');
    Route::get('/teacher/advisory/{section}/class-list/import/{import}/status', [TeacherSectionController::class, 'advisoryClassListImportStatus'])
        ->middleware('teacher')
        ->name('teacher.advisory.class-list.import-status');
    Route::get('/teacher/advisory/{section}/students/{enrollment}/profile', [TeacherSectionController::class, 'advisoryStudentProfile'])
        ->middleware('teacher')
        ->name('teacher.advisory.students.profile');
    Route::get('/teacher/advisory/{section}/class-list', [TeacherSectionController::class, 'advisoryClassList'])
        ->middleware('teacher')
        ->name('teacher.advisory.class-list.index');
    Route::get('/teacher/advisory/{section}/class-list/sf1', [TeacherSectionController::class, 'downloadAdvisorySf1'])
        ->middleware('teacher')
        ->name('teacher.advisory.class-list.sf1');
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
    Route::post('/teacher/sections/{assignment}/grades', [TeacherSectionController::class, 'storeGrades'])
        ->middleware('teacher')
        ->name('teacher.sections.grades.store');
    Route::post('/teacher/sections/{assignment}/grades/import', [TeacherSectionController::class, 'importGrades'])
        ->middleware('teacher')
        ->name('teacher.sections.grades.import');
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
    Route::get('/registrar/students/{student}/documents/{document}/view', [RegistrarDashboardController::class, 'viewDocument'])
        ->middleware('registrar')
        ->name('registrar.students.documents.view');
    Route::get('/registrar/students/{student}/enrollments/{enrollment}/sf9', [RegistrarDashboardController::class, 'studentSf9'])
        ->middleware('registrar')
        ->name('registrar.students.sf9');
    Route::get('/registrar/students/{student}/sf10', [RegistrarDashboardController::class, 'studentSf10'])
        ->middleware('registrar')
        ->name('registrar.students.sf10');
    Route::get('/registrar/grade-approvals', [RegistrarDashboardController::class, 'gradeApprovals'])
        ->middleware('registrar')
        ->name('registrar.grade-approvals');
    Route::post('/registrar/grade-approvals/approve-selected', [RegistrarDashboardController::class, 'approveSelectedGrades'])
        ->middleware('registrar')
        ->name('registrar.grade-approvals.approve-selected');
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
    Route::get('/registrar/classes/{section}', [RegistrarDashboardController::class, 'classStatus'])
        ->middleware('registrar')
        ->name('registrar.classes.status');
    Route::get('/registrar/classes/{section}/subjects', [RegistrarDashboardController::class, 'sectionSubjects'])
        ->middleware('registrar')
        ->name('registrar.classes.subjects');
    Route::get('/registrar/class-subjects/{assignment}', [RegistrarDashboardController::class, 'showClassSubject'])
        ->middleware('registrar')
        ->name('registrar.class-subjects.show');
    Route::get('/registrar/class-subjects/{assignment}/grade-records', [RegistrarDashboardController::class, 'classSubjectGradeRecords'])
        ->middleware('registrar')
        ->name('registrar.class-subjects.grade-records');
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
    Route::get('/principal/promotions', [GuidanceDashboardController::class, 'promotions'])
        ->middleware('principal')
        ->name('principal.promotions.index');
    Route::post('/principal/promotions/sf5', [GuidanceDashboardController::class, 'downloadPromotionSf5'])
        ->middleware('principal')
        ->name('principal.promotions.sf5');
    Route::get('/principal/reports/age-for-grade', [PrincipalDashboardController::class, 'ageForGradeReport'])
        ->middleware('principal')
        ->name('principal.reports.age-for-grade');
    Route::get('/principal/reports/placement-test-recommendations/download', [GuidanceDashboardController::class, 'downloadPlacementTestRecommendations'])
        ->middleware('principal')
        ->name('principal.reports.placement-test-recommendations.download');
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
    Route::post('/guidance/promotions/sf5', [GuidanceDashboardController::class, 'downloadPromotionSf5'])
        ->middleware('guidance')
        ->name('guidance.promotions.sf5');
    Route::post('/guidance/promotions/bulk', [GuidanceDashboardController::class, 'bulkPromote'])
        ->middleware('guidance')
        ->name('guidance.promotions.bulk');
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
    Route::get('/guidance/reports/promotion', [\App\Http\Controllers\Guidance\PromotionReportController::class, 'index'])
        ->middleware('guidance')
        ->name('guidance.reports.promotion');
    Route::get('/guidance/reports/enrollment', [\App\Http\Controllers\Guidance\EnrollmentReportController::class, 'index'])
        ->middleware('guidance')
        ->name('guidance.reports.enrollment');
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
