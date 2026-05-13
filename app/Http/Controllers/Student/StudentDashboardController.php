<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PreferredCourse;
use App\Models\StudentAddress;
use App\Models\StudentGuardian;
use App\Models\StudentProfile;
use App\Models\StudentDocument;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user();
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $currentEnrollment = null;

        if ($student && $activeYear) {
            $currentEnrollment = Enrollment::query()
                ->with(['section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse', 'academicYear'])
                ->where('student_ID', $student->id)
                ->where('SY_ID', $activeYear->SY_ID)
                ->latest('created_at')
                ->first();
        }

        return view('users.student.dashboard', [
            'student' => $student,
            'application' => $student?->application,
            'activeYear' => $activeYear,
            'currentEnrollment' => $currentEnrollment,
        ]);
    }

    public function enrollment(Request $request): View
    {
        $student = $request->user();
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $clusters = Cluster::query()
            ->with(['preferredCourses' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get(['cluster_ID', 'name']);
        $selectedEnrollmentLevel = $request->old('enrollment_level', $request->string('enrollment_level')->toString());
        if (! in_array($selectedEnrollmentLevel, ['junior', 'senior'], true)) {
            $selectedEnrollmentLevel = null;
        }
        $hasEnrollment = false;
        $currentEnrollment = null;

        if ($student && $activeYear) {
            $currentEnrollment = Enrollment::query()
                ->with(['section.adviser', 'section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse', 'academicYear'])
                ->where('student_ID', $student->id)
                ->where('SY_ID', $activeYear->SY_ID)
                ->latest('created_at')
                ->first();

            $hasEnrollment = $currentEnrollment !== null;
        }

        return view('users.student.enrollment', [
            'student' => $student,
            'application' => $student?->application,
            'clusters' => $clusters,
            'gradeLevels' => GradeLevel::options(),
            'selectedEnrollmentLevel' => $selectedEnrollmentLevel,
            'activeYear' => $activeYear,
            'hasEnrollment' => $hasEnrollment,
            'currentEnrollment' => $currentEnrollment,
        ]);
    }

    public function profile(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $student = $request->user();
        $student?->load([
                'profile',
                'guardians',
                'addresses',
                'documents',
                'enrollments' => function ($query) {
                    $query->with(['section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse', 'academicYear'])
                        ->latest('created_at');
                },
            ]);

        $currentEnrollment = null;

        if ($student && $activeYear) {
            $currentEnrollment = $student->enrollments
                ->firstWhere('SY_ID', $activeYear->SY_ID);
        }

        return view('users.student.profile', [
            'student' => $student,
            'application' => $student?->application,
            'profile' => $student?->profile,
            'documents' => $student?->documents?->sortByDesc('created_at')->values() ?? collect(),
            'enrollments' => $student?->enrollments ?? collect(),
            'currentEnrollment' => $currentEnrollment,
            'activeYear' => $activeYear,
        ]);
    }

    public function documents(Request $request): View
    {
        $student = $request->user();
        
        if (!$student) {
            return view('users.student.documents', [
                'student' => null,
                'application' => null,
                'documents' => collect(),
            ]);
        }

        $documents = StudentDocument::query()
            ->where('student_ID', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.student.documents', [
            'student' => $student,
            'application' => $student?->application,
            'documents' => $documents,
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $student = $request->user();

        if (!$student) {
            return back()->withErrors(['error' => 'Student profile not found.']);
        }

        $validated = $request->validate([
            'doc_type' => ['required', 'in:birth_certificate,form_137,good_moral,id_photo,other'],
            'document' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        try {
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('student_documents', $fileName, 'public');

                // Delete existing document of same type if it exists
                StudentDocument::query()
                    ->where('student_ID', $student->id)
                    ->where('doc_type', $validated['doc_type'])
                    ->delete();

                StudentDocument::create([
                    'student_ID' => $student->id,
                    'doc_type' => $validated['doc_type'],
                    'file_path' => $filePath,
                    'status' => 'pending',
                    'date_uploaded' => now(),
                ]);
            }

            return back()->with('success', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to upload document: ' . $e->getMessage()]);
        }
    }

    public function deleteDocument(StudentDocument $document)
    {
        $student = request()->user();

        if (!$student || $document->student_ID !== $student->id) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        try {
            // Delete file from storage
            if ($document->file_path && \Storage::disk('public')->exists($document->file_path)) {
                \Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return back()->with('success', 'Document deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete document: ' . $e->getMessage()]);
        }
    }

    public function viewDocument(StudentDocument $document): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $student = request()->user();

        if (! $student || $document->student_ID !== $student->id) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            return back()->withErrors(['error' => 'Document file was not found.']);
        }

        return Storage::disk('public')->response($document->file_path);
    }

    public function grades(Request $request): View
    {
        $student = $request->user();
        
        if (!$student) {
            return view('users.student.grades', [
                'student' => null,
                'application' => null,
                'enrollments' => collect(),
            ]);
        }

        // Get all student enrollments with their subject assignments
        $enrollments = Enrollment::query()
            ->with([
                'academicYear',
                'section.gradeLevel',
                'gradeLevel',
                'cluster',
                'preferredCourse',
                'subjectAssignments' => function ($query) use ($student) {
                    $query->with([
                        'curriculumSubject.subject',
                        'section',
                        'academicYear',
                        'grades' => function ($query) {
                            $query->where('status', 'released'); // Only get released grades
                        }
                    ]);
                }
            ])
            ->where('student_ID', $student->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($enrollment) {
                return $enrollment->academicYear->school_year;
            });

        return view('users.student.grades', [
            'student' => $student,
            'application' => $student?->application,
            'enrollments' => $enrollments,
        ]);
    }

    public function storeEnrollment(Request $request)
    {
        $student = $request->user();

        if (! $student) {
            return back()->withErrors(['enrollment' => 'Student profile not found.'])->withInput();
        }

        $activeYear = AcademicYear::query()->where('status', true)->first();

        if (! $activeYear) {
            return back()->withErrors(['enrollment' => 'No active school year is configured.'])->withInput();
        }

        $hasEnrollment = Enrollment::query()
            ->where('student_ID', $student->id)
            ->where('SY_ID', $activeYear->SY_ID)
            ->exists();

        if ($hasEnrollment) {
            return back()->withErrors(['enrollment' => 'You already submitted an enrollment for the current school year.'])->withInput();
        }

        $validated = $request->validate([
            'enrollment_level' => ['required', 'in:junior,senior'],
            'grade_level' => ['required', 'in:7,8,9,10,11,12'],
            'LRN' => ['required', 'digits:12'],
            'semester' => ['nullable', 'in:first,second'],
            'cluster_ID' => ['nullable', 'integer', 'exists:clusters,cluster_ID'],
            'course_ID' => ['nullable', 'integer', 'exists:preferred_courses,course_ID'],
            'learner_type' => ['required', 'in:regular,transferee,returnee'],
            'last_grade_level_completed' => ['nullable', 'string', 'max:255'],
            'last_school_year_completed' => ['nullable', 'string', 'max:255'],
            'last_school_attended' => ['nullable', 'string', 'max:255'],
            'school_id_from_previous_school' => ['nullable', 'string', 'max:255'],
            'birthplace' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:Male,Female'],
            'contact_no' => ['nullable', 'string', 'max:30'],
            'religion' => ['nullable', 'string', 'max:255'],
            'mother_tongue' => ['nullable', 'string', 'max:255'],
            'ip_community' => ['required', 'in:No,Yes'],
            'ip_details' => ['nullable', 'string', 'max:255'],
            'four_ps_beneficiary' => ['required', 'in:No,Yes'],
            'four_ps_details' => ['nullable', 'string', 'max:255'],
            'pwd' => ['required', 'in:No,Yes'],
            'pwd_details' => ['nullable', 'string', 'max:255'],
            'curr_house_no' => ['nullable', 'string', 'max:255'],
            'curr_street_name' => ['nullable', 'string', 'max:255'],
            'curr_barangay' => ['nullable', 'string', 'max:255'],
            'curr_municipality_city' => ['nullable', 'string', 'max:255'],
            'curr_province' => ['nullable', 'string', 'max:255'],
            'curr_country' => ['nullable', 'string', 'max:255'],
            'curr_zip_code' => ['nullable', 'string', 'max:255'],
            'perm_house_no' => ['nullable', 'string', 'max:255'],
            'perm_street_name' => ['nullable', 'string', 'max:255'],
            'perm_barangay' => ['nullable', 'string', 'max:255'],
            'perm_municipality_city' => ['nullable', 'string', 'max:255'],
            'perm_province' => ['nullable', 'string', 'max:255'],
            'perm_country' => ['nullable', 'string', 'max:255'],
            'perm_zip_code' => ['nullable', 'string', 'max:255'],
            'father_lname' => ['nullable', 'string', 'max:255'],
            'father_fname' => ['nullable', 'string', 'max:255'],
            'father_mname' => ['nullable', 'string', 'max:255'],
            'father_suffix' => ['nullable', 'string', 'max:255'],
            'father_contact_no' => ['nullable', 'string', 'max:255'],
            'mother_lname' => ['nullable', 'string', 'max:255'],
            'mother_fname' => ['nullable', 'string', 'max:255'],
            'mother_mname' => ['nullable', 'string', 'max:255'],
            'mother_suffix' => ['nullable', 'string', 'max:255'],
            'mother_contact_no' => ['nullable', 'string', 'max:255'],
            'guardian_lname' => ['nullable', 'string', 'max:255'],
            'guardian_fname' => ['nullable', 'string', 'max:255'],
            'guardian_mname' => ['nullable', 'string', 'max:255'],
            'guardian_suffix' => ['nullable', 'string', 'max:255'],
            'guardian_contact_no' => ['nullable', 'string', 'max:255'],
            'same_address' => ['nullable', 'boolean'],
        ]);

        if ($student->lrn !== $validated['LRN']) {
            return back()->withErrors(['LRN' => 'LRN does not match your student record.'])->withInput();
        }

        $gradeLevelEnum = 'grade_'.$validated['grade_level'];
        $gradeId = GradeLevel::idForValue($gradeLevelEnum);
        $isSeniorHigh = $validated['enrollment_level'] === 'senior';

        if (! $gradeId) {
            return back()->withErrors(['grade_level' => 'Selected grade level is not configured.'])->withInput();
        }

        if ($validated['enrollment_level'] === 'junior' && ! in_array($validated['grade_level'], ['7', '8', '9', '10'], true)) {
            return back()->withErrors(['grade_level' => 'Junior high school enrollment is only for Grades 7 to 10.'])->withInput();
        }

        if ($validated['enrollment_level'] === 'senior' && ! in_array($validated['grade_level'], ['11', '12'], true)) {
            return back()->withErrors(['grade_level' => 'Senior high school enrollment is only for Grades 11 to 12.'])->withInput();
        }

        if ($isSeniorHigh) {
            if (! $validated['semester']) {
                return back()->withErrors(['semester' => 'Semester is required for Grade 11 or 12.'])->withInput();
            }

            if (! $validated['cluster_ID']) {
                return back()->withErrors(['cluster_ID' => 'Cluster is required for Grade 11 or 12.'])->withInput();
            }

            if (! $validated['course_ID']) {
                return back()->withErrors(['course_ID' => 'Preferred course is required for Grade 11 or 12.'])->withInput();
            }

            $courseBelongsToCluster = PreferredCourse::query()
                ->where('course_ID', $validated['course_ID'])
                ->where('cluster_ID', $validated['cluster_ID'])
                ->exists();

            if (! $courseBelongsToCluster) {
                return back()->withErrors(['course_ID' => 'Preferred course must belong to the selected cluster.'])->withInput();
            }
        }

        if (in_array($validated['learner_type'], ['transferee', 'returnee'], true)) {
            $requiredFields = [
                'last_grade_level_completed',
                'last_school_year_completed',
                'last_school_attended',
                'school_id_from_previous_school',
            ];
            foreach ($requiredFields as $field) {
                if (empty($validated[$field])) {
                    return back()->withErrors([$field => 'This field is required for transferees or returning learners.'])->withInput();
                }
            }
        }

        if ($validated['ip_community'] === 'Yes' && empty($validated['ip_details'])) {
            return back()->withErrors(['ip_details' => 'Please specify the IP/Community.'])->withInput();
        }

        if ($validated['four_ps_beneficiary'] === 'Yes' && empty($validated['four_ps_details'])) {
            return back()->withErrors(['four_ps_details' => 'Please provide the 4Ps Household ID Number.'])->withInput();
        }

        if ($validated['pwd'] === 'Yes' && empty($validated['pwd_details'])) {
            return back()->withErrors(['pwd_details' => 'Please specify the disability.'])->withInput();
        }

        if (! empty($validated['same_address'])) {
            $validated['perm_house_no'] = $validated['curr_house_no'] ?? null;
            $validated['perm_street_name'] = $validated['curr_street_name'] ?? null;
            $validated['perm_barangay'] = $validated['curr_barangay'] ?? null;
            $validated['perm_municipality_city'] = $validated['curr_municipality_city'] ?? null;
            $validated['perm_province'] = $validated['curr_province'] ?? null;
            $validated['perm_country'] = $validated['curr_country'] ?? null;
            $validated['perm_zip_code'] = $validated['curr_zip_code'] ?? null;
        }

        DB::transaction(function () use ($student, $activeYear, $validated, $gradeId, $isSeniorHigh): void {
            $enrollment = Enrollment::query()->create([
                'student_ID' => $student->id,
                'section_ID' => null,
                'SY_ID' => $activeYear->SY_ID,
                'cluster_ID' => $isSeniorHigh ? $validated['cluster_ID'] : null,
                'course_ID' => $isSeniorHigh ? $validated['course_ID'] : null,
                'grade_ID' => $gradeId,
                'semester' => $isSeniorHigh ? $validated['semester'] : null,
                'learner_type' => $validated['learner_type'],
                'enrollment_status' => 'pending',
            ]);

            StudentProfile::query()->updateOrCreate(
                ['student_ID' => $student->id],
                [
                    'is_4ps' => $validated['four_ps_beneficiary'] === 'Yes',
                    'four_ps_household_id' => $validated['four_ps_details'] ?? null,
                    'is_ip' => $validated['ip_community'] === 'Yes',
                    'ip_community' => $validated['ip_details'] ?? null,
                    'has_disability' => $validated['pwd'] === 'Yes',
                    'disability_name' => $validated['pwd_details'] ?? null,
                ]
            );

            StudentGuardian::query()->updateOrCreate(
                ['student_ID' => $student->id, 'relationship' => 'father'],
                [
                    'first_name' => $validated['father_fname'] ?? null,
                    'middle_name' => $validated['father_mname'] ?? null,
                    'last_name' => $validated['father_lname'] ?? null,
                    'suffix' => $validated['father_suffix'] ?? null,
                    'contact_no' => $validated['father_contact_no'] ?? null,
                ]
            );

            StudentGuardian::query()->updateOrCreate(
                ['student_ID' => $student->id, 'relationship' => 'mother'],
                [
                    'first_name' => $validated['mother_fname'] ?? null,
                    'middle_name' => $validated['mother_mname'] ?? null,
                    'last_name' => $validated['mother_lname'] ?? null,
                    'suffix' => $validated['mother_suffix'] ?? null,
                    'contact_no' => $validated['mother_contact_no'] ?? null,
                ]
            );

            StudentGuardian::query()->updateOrCreate(
                ['student_ID' => $student->id, 'relationship' => 'guardian'],
                [
                    'first_name' => $validated['guardian_fname'] ?? null,
                    'middle_name' => $validated['guardian_mname'] ?? null,
                    'last_name' => $validated['guardian_lname'] ?? null,
                    'suffix' => $validated['guardian_suffix'] ?? null,
                    'contact_no' => $validated['guardian_contact_no'] ?? null,
                ]
            );

            StudentAddress::query()->updateOrCreate(
                ['student_ID' => $student->id, 'address_type' => 'current'],
                [
                    'house_no' => $validated['curr_house_no'] ?? null,
                    'street_name' => $validated['curr_street_name'] ?? null,
                    'barangay' => $validated['curr_barangay'] ?? null,
                    'municipality' => $validated['curr_municipality_city'] ?? null,
                    'province' => $validated['curr_province'] ?? null,
                    'country' => $validated['curr_country'] ?? null,
                    'zip_code' => $validated['curr_zip_code'] ?? null,
                ]
            );

            StudentAddress::query()->updateOrCreate(
                ['student_ID' => $student->id, 'address_type' => 'permanent'],
                [
                    'house_no' => $validated['perm_house_no'] ?? null,
                    'street_name' => $validated['perm_street_name'] ?? null,
                    'barangay' => $validated['perm_barangay'] ?? null,
                    'municipality' => $validated['perm_municipality_city'] ?? null,
                    'province' => $validated['perm_province'] ?? null,
                    'country' => $validated['perm_country'] ?? null,
                    'zip_code' => $validated['perm_zip_code'] ?? null,
                ]
            );

            $student->update([
                'sex' => isset($validated['gender']) ? strtolower($validated['gender']) : $student->sex,
                'birthplace' => $validated['birthplace'] ?? $student->birthplace,
                'mother_tongue' => $validated['mother_tongue'] ?? $student->mother_tongue,
                'religion' => $validated['religion'] ?? $student->religion,
            ]);
        });

        return redirect()->route('student.enrollment')->with('status', 'Enrollment submitted successfully.');
    }
}
