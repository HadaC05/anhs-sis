<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\FilterStudentSubjectsRequest;
use App\Http\Requests\Student\StoreStudentDocumentsRequest;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeStatus;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\StudentApplication;
use App\Models\StudentDocument;
use App\Models\StudentGuardian;
use App\Models\StudentProfile;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use App\Support\StudentDocumentUploader;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
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
                ->with(['section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse', 'academicYear', 'enrollmentStatus', 'placementStatus'])
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
            'profileCompletionRequired' => $student instanceof Student && ! $student->hasCompleteProfile(),
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
            'enrollments' => function ($query) {
                $query->with(['section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse', 'academicYear', 'enrollmentStatus'])
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
            'enrollments' => $student?->enrollments ?? collect(),
            'currentEnrollment' => $currentEnrollment,
            'activeYear' => $activeYear,
            'religions' => Religion::options(),
            'suffixOptions' => StudentApplication::suffixOptions(),
            'earliestBirthdate' => StudentApplication::EARLIEST_BIRTHDATE,
        ]);
    }

    public function updateProfile(UpdateStudentProfileRequest $request): RedirectResponse
    {
        $student = $request->user();
        $validated = $request->validated();

        if (! empty($validated['same_address'])) {
            $validated['perm_house_no'] = $validated['curr_house_no'] ?? null;
            $validated['perm_street_name'] = $validated['curr_street_name'] ?? null;
            $validated['perm_barangay'] = $validated['curr_barangay'] ?? null;
            $validated['perm_municipality_city'] = $validated['curr_municipality_city'] ?? null;
            $validated['perm_province'] = $validated['curr_province'] ?? null;
            $validated['perm_country'] = $validated['curr_country'] ?? null;
            $validated['perm_zip_code'] = $validated['curr_zip_code'] ?? null;
        }

        $validated['curr_country'] = $validated['curr_country'] ?: 'Philippines';
        $validated['perm_country'] = $validated['perm_country'] ?: 'Philippines';

        DB::transaction(function () use ($student, $validated): void {
            $student->update([
                'email' => $validated['email'] ?? null,
                'contact_no' => $validated['contact_no'],
                'sex' => strtolower($validated['gender']),
                'birthdate' => $validated['birthdate'],
                'birthplace' => $validated['birthplace'],
                'mother_tongue' => $validated['mother_tongue'],
                'religion' => $validated['religion'],
            ]);

            StudentProfile::query()->updateOrCreate(
                ['student_ID' => $student->id],
                [
                    'is_4ps' => $validated['four_ps_beneficiary'] === 'Yes',
                    'four_ps_household_id' => $validated['four_ps_beneficiary'] === 'Yes' ? ($validated['four_ps_details'] ?? null) : null,
                    'is_ip' => $validated['ip_community'] === 'Yes',
                    'ip_community' => $validated['ip_community'] === 'Yes' ? ($validated['ip_details'] ?? null) : null,
                    'has_disability' => $validated['pwd'] === 'Yes',
                    'disability_name' => $validated['pwd'] === 'Yes' ? ($validated['pwd_details'] ?? null) : null,
                ]
            );

            foreach ([
                'current' => ['curr_house_no', 'curr_street_name', 'curr_barangay', 'curr_municipality_city', 'curr_province', 'curr_country', 'curr_zip_code'],
                'permanent' => ['perm_house_no', 'perm_street_name', 'perm_barangay', 'perm_municipality_city', 'perm_province', 'perm_country', 'perm_zip_code'],
            ] as $type => [$house, $street, $barangay, $municipality, $province, $country, $zip]) {
                StudentAddress::query()->updateOrCreate(
                    [
                        'student_ID' => $student->id,
                        'address_type' => $type,
                    ],
                    [
                        'house_no' => $validated[$house] ?? null,
                        'street_name' => $validated[$street] ?? null,
                        'barangay' => $validated[$barangay] ?? null,
                        'municipality' => $validated[$municipality] ?? null,
                        'province' => $validated[$province] ?? null,
                        'country' => $validated[$country] ?? null,
                        'zip_code' => $validated[$zip] ?? null,
                    ]
                );
            }

            foreach (['father', 'mother', 'guardian'] as $relationship) {
                StudentGuardian::query()->updateOrCreate(
                    [
                        'student_ID' => $student->id,
                        'relationship' => $relationship,
                    ],
                    StudentGuardian::attributesFromForm($validated, $relationship)
                );
            }
        });

        return back()->with('status', 'Student profile updated.');
    }

    public function documents(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $student = $request->user();
        $currentEnrollment = null;

        if ($student && $activeYear) {
            $currentEnrollment = Enrollment::query()
                ->with(['section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse', 'academicYear', 'enrollmentStatus'])
                ->where('student_ID', $student->id)
                ->where('SY_ID', $activeYear->SY_ID)
                ->latest('created_at')
                ->first();
        }

        $documents = $student
            ? StudentDocument::query()
                ->with('returnReason')
                ->where('student_ID', $student->id)
                ->orderByDesc('date_uploaded')
                ->orderByDesc('created_at')
                ->get()
                ->unique('doc_type')
                ->values()
            : collect();

        return view('users.student.documents', [
            'student' => $student,
            'application' => $student?->application,
            'documents' => $documents,
            'documentTypes' => StudentDocument::typeDefinitions(),
            'currentEnrollment' => $currentEnrollment,
            'activeYear' => $activeYear,
        ]);
    }

    public function uploadDocument(StoreStudentDocumentsRequest $request): RedirectResponse
    {
        $student = $request->user();

        if (! $student instanceof Student) {
            return back()->withErrors(['error' => 'Student profile not found.']);
        }

        $uploads = $request->uploads();

        $verifiedTypes = StudentDocument::query()
            ->where('student_ID', $student->id)
            ->whereIn('doc_type', array_keys($uploads))
            ->where('status', 'verified')
            ->get()
            ->pluck('doc_type');

        if ($verifiedTypes->isNotEmpty()) {
            return back()->withErrors(['error' => 'This document has been verified and cannot be replaced. Ask the guidance counselor to unverify it first.']);
        }

        try {
            $stored = StudentDocumentUploader::storeMany($student, $uploads);
            $count = count($stored);

            return back()->with(
                'success',
                $count === 1
                    ? 'Document uploaded successfully.'
                    : $count.' documents uploaded successfully.'
            );
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to upload document: '.$e->getMessage()]);
        }
    }

    public function deleteDocument(StudentDocument $document): RedirectResponse
    {
        $student = request()->user();

        if (! $student || $document->student_ID !== $student->id) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        if ($document->isVerified()) {
            return back()->withErrors(['error' => 'This document has been verified and cannot be deleted. Ask the guidance counselor to unverify it first.']);
        }

        try {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return back()->with('success', 'Document deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete document: '.$e->getMessage()]);
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

        if (! $student) {
            return view('users.student.grades', [
                'student' => null,
                'application' => null,
                'enrollments' => collect(),
                'selectedEnrollment' => null,
                'gradingTerms' => GradingTerm::activePeriods(),
            ]);
        }

        $enrollments = Enrollment::query()
            ->with([
                'academicYear',
                'section.gradeLevel',
                'section.curriculum',
                'gradeLevel',
                'cluster',
                'preferredCourse',
                'enrollmentStatus',
            ])
            ->where('student_ID', $student->id)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->orderBy('created_at', 'desc')
            ->get();

        $enrollments->each(function (Enrollment $enrollment): void {
            $assignments = $this->subjectAssignmentsForEnrollment($enrollment);

            $grades = StudentSubjectGrade::query()
                ->whereHas('studentSubject', fn ($query) => $query->where('enrollment_ID', $enrollment->enrollment_ID))
                ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
                ->whereStatus(GradeStatus::RELEASED)
                ->get()
                ->groupBy('assignment_ID');

            $assignments->each(function (TeacherSubjectAssignment $assignment) use ($grades): void {
                $assignment->setRelation(
                    'grades',
                    $grades->get($assignment->assignment_ID, collect())
                );
            });

            $enrollment->setRelation('subjectAssignments', $assignments);
        });

        $selectedEnrollmentId = (int) $request->query('session', 0);
        $selectedEnrollment = $enrollments->firstWhere('enrollment_ID', $selectedEnrollmentId)
            ?? $enrollments->first();

        return view('users.student.grades', [
            'student' => $student,
            'application' => $student?->application,
            'enrollments' => $enrollments,
            'selectedEnrollment' => $selectedEnrollment,
            'gradingTerms' => GradingTerm::periodsForSection(
                $selectedEnrollment?->section,
                $selectedEnrollment?->semester,
            ),
        ]);
    }

    public function subjects(FilterStudentSubjectsRequest $request): View
    {
        $student = $request->user();
        $filters = $request->validated();

        $enrollments = Enrollment::query()
            ->with([
                'academicYear',
                'section.gradeLevel',
                'gradeLevel',
                'gradingSemester',
                'cluster',
                'preferredCourse',
                'enrollmentStatus',
            ])
            ->where('student_ID', $student->id)
            ->orderByDesc('created_at')
            ->get();

        // Filters intentionally come from the student's own enrollment history,
        // so a learner cannot browse offerings that were never assigned to them.
        $gradeLevels = $enrollments
            ->map(fn (Enrollment $enrollment) => $enrollment->getRelation('gradeLevel'))
            ->filter()
            ->unique('grade_ID')
            ->sortBy('grade_ID')
            ->values();
        $selectedGrade = $gradeLevels->firstWhere('grade_ID', (int) ($filters['grade_ID'] ?? 0))
            ?? $gradeLevels->first();
        $gradeEnrollments = $enrollments
            ->filter(fn (Enrollment $enrollment): bool => (int) $enrollment->getRelation('gradeLevel')?->grade_ID === (int) $selectedGrade?->grade_ID)
            ->values();
        $isSeniorHigh = in_array($selectedGrade?->grade_label, ['Grade 11', 'Grade 12'], true);
        $semesters = $isSeniorHigh
            ? $gradeEnrollments
                ->map(fn (Enrollment $enrollment) => $enrollment->gradingSemester)
                ->filter(fn ($semester): bool => $semester !== null && in_array($semester->key, [GradingSemester::FIRST, GradingSemester::SECOND], true))
                ->unique('semester_ID')
                ->sortBy('sort_order')
                ->values()
            : collect();
        $selectedSemester = $semesters->firstWhere('semester_ID', (int) ($filters['semester_ID'] ?? 0))
            ?? $semesters->first();
        $selectedEnrollment = $isSeniorHigh
            ? $gradeEnrollments->first(
                fn (Enrollment $enrollment): bool => (int) $enrollment->gradingSemester?->semester_ID === (int) $selectedSemester?->semester_ID
            )
            : $gradeEnrollments->first();
        $studentSubjects = $selectedEnrollment
            ? StudentSubject::query()
                ->with(['curriculumSubject.subject', 'curriculumSubject.gradingSemester'])
                ->where('enrollment_ID', $selectedEnrollment->enrollment_ID)
                ->orderBy('curr_subj_ID')
                ->get()
            : collect();

        return view('users.student.subjects', [
            'student' => $student,
            'application' => $student?->application,
            'selectedEnrollment' => $selectedEnrollment,
            'studentSubjects' => $studentSubjects,
            'gradeLevels' => $gradeLevels,
            'selectedGrade' => $selectedGrade,
            'isSeniorHigh' => $isSeniorHigh,
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
        ]);
    }

    /**
     * @return EloquentCollection<int, TeacherSubjectAssignment>
     */
    private function subjectAssignmentsForEnrollment(Enrollment $enrollment, ?string $semester = null): EloquentCollection
    {
        if (! $enrollment->section_ID) {
            return new EloquentCollection;
        }

        $semesterKey = $semester ?? $enrollment->semester;

        return TeacherSubjectAssignment::query()
            ->with(['curriculumSubject.subject', 'section', 'academicYear', 'staff'])
            ->where('section_ID', $enrollment->section_ID)
            ->where('SY_ID', $enrollment->SY_ID)
            ->when($semesterKey, function ($query) use ($semesterKey): void {
                $query->whereHas('curriculumSubject', function ($subjectQuery) use ($semesterKey): void {
                    $subjectQuery->whereHas('gradingSemester', function ($semesterQuery) use ($semesterKey): void {
                        $semesterQuery->whereIn('key', [\App\Models\GradingSemester::FULL_YEAR, $semesterKey]);
                    });
                });
            })
            ->orderBy('curr_subj_ID')
            ->get();
    }
}
