<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentAccountPasswordRequest;
use App\Http\Requests\Student\UpdateStudentAccountRequest;
use App\Models\DocumentType;
use App\Models\Student;
use App\Support\StudentDocumentUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    public function edit(): View
    {
        return view('users.student.account', [
            'student' => request()->user(),
        ]);
    }

    public function update(UpdateStudentAccountRequest $request): RedirectResponse
    {
        $student = $request->user();

        if ($student instanceof Student && $request->file('photo') instanceof UploadedFile) {
            StudentDocumentUploader::store($student, DocumentType::ID_PHOTO, $request->file('photo'));
            $student->unsetRelation('photoDocument');
        }

        return back()->with('status', 'Account profile updated.');
    }

    public function updatePassword(UpdateStudentAccountPasswordRequest $request): RedirectResponse
    {
        $student = $request->user();

        $student->update([
            'password' => $request->validated('password'),
            'change_password' => false,
            'password_changed_at' => now(),
        ]);

        return back()->with('status', 'Password updated.');
    }
}
