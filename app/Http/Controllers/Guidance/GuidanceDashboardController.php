<?php

namespace App\Http\Controllers\Guidance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guidance\BulkUpdateEnrollmentStatusRequest;
use App\Http\Requests\Guidance\BulkVerifyStudentDocumentsRequest;
use App\Http\Requests\Guidance\RejectStudentDocumentRequest;
use App\Http\Requests\Guidance\StoreSectionRequest;
use App\Http\Requests\Guidance\UpdateEnrollmentStatusRequest;
use App\Http\Requests\Guidance\UpdatePlacementStatusRequest;
use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\DocumentReturnReason;
use App\Models\DocumentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Models\PromotionStatus;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StudentDocument;
use App\Notifications\DocumentStatusUpdated;
use App\Support\EnrollmentDashboardData;
use App\Support\EnrollmentDocumentCompletion;
use App\Support\PlacementAssessmentAdvisor;
use App\Support\PromotionEligibility;
use App\Support\StudentAccountProvisioner;
use App\Support\StudentEnrollmentNotifier;
use App\Support\StudentPlacementTestNotifier;
use App\Support\VacantSectionAssigner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuidanceDashboardController extends Controller
{
    public function promotions(): View
    {
        return view('users.guidance.promotions.index', [
            'enrollments' => Enrollment::query()
                ->with(['student.application', 'gradeLevel', 'academicYear', 'promotionStatus'])
                ->where('promotion_status_ID', PromotionStatus::idFor(PromotionStatus::ELIGIBLE))
                ->latest('SY_ID')
                ->get(),
            'academicYears' => AcademicYear::query()->orderBy('start_date')->get(),
        ]);
    }

    public function confirmPromotion(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate(['SY_ID' => ['required', 'integer', 'exists:academic_years,SY_ID']]);
        $enrollment->loadMissing('academicYear');
        $evaluation = PromotionEligibility::evaluate($enrollment);
        $enrollment->update(['promotion_status' => $evaluation['status']]);

        if ($evaluation['status'] !== PromotionStatus::ELIGIBLE) {
            return back()->withErrors(['promotion' => 'This learner is no longer eligible: '.$evaluation['reason']]);
        }

        $targetYear = AcademicYear::query()->findOrFail($validated['SY_ID']);
        if ($targetYear->start_date->lessThanOrEqualTo($enrollment->academicYear?->start_date)) {
            return back()->withErrors(['promotion' => 'Select a school year after the learner’s current enrollment.']);
        }

        $nextGrade = GradeLevel::query()->where('grade_ID', '>', $enrollment->grade_ID)->orderBy('grade_ID')->first();
        if (! $nextGrade) {
            return back()->withErrors(['promotion' => 'Grade 12 completers cannot be promoted to another grade level.']);
        }

        $nextEnrollment = DB::transaction(fn (): Enrollment => Enrollment::query()->firstOrCreate(
            ['student_ID' => $enrollment->student_ID, 'SY_ID' => $targetYear->SY_ID],
            [
                'grade_ID' => $nextGrade->grade_ID,
                'semester' => null,
                'learner_type' => LearnerType::REGULAR,
                'enrollment_status' => EnrollmentStatus::PENDING,
                'promotion_status' => PromotionStatus::PENDING,
            ],
        ));

        return redirect()->route('guidance.enrollments.show', $nextEnrollment)
            ->with('status', 'Promotion confirmed. A pending enrollment for '.$nextGrade->grade_label.' was created; assign the learner to a section to finish enrollment.');
    }

    public function index(Request $request): View
    {
        return view('users.guidance.dashboard', EnrollmentDashboardData::forRequest($request));
    }

    public function enrollments(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());
        $learnerType = $request->string('learner_type')->toString();
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $academicYearId = $request->string('academic_year_id')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $perPage = (int) $request->integer('per_page', 15);

        $enrollmentsQuery = Enrollment::query()
            ->with([
                'student.application',
                'student.profile',
                'student.guardians',
                'student.addresses',
                'cluster',
                'preferredCourse',
                'gradeLevel',
                'academicYear',
                'section.gradeLevel',
                'enrollmentStatus',
                'learnerType',
                'placementStatus',
            ])
            ->when($academicYearId !== '' && $academicYearId !== 'all', function ($query) use ($academicYearId) {
                $query->where('SY_ID', $academicYearId);
            })
            ->when($academicYearId === '' && $activeYear, function ($query) use ($activeYear) {
                $query->where('SY_ID', $activeYear->SY_ID);
            })
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $statusId = EnrollmentStatus::idFor($status);

                if ($statusId !== null) {
                    $query->where('enrollment_status_ID', $statusId);
                }
            }, function ($query) use ($status) {
                if ($status === '') {
                    $query->where('enrollment_status_ID', EnrollmentStatus::idFor(EnrollmentStatus::TEMPORARILY_ENROLLED));
                }
            })
            ->when($learnerType !== '', function ($query) use ($learnerType) {
                $learnerTypeId = LearnerType::idFor($learnerType);

                if ($learnerTypeId !== null) {
                    $query->where('learner_type_ID', $learnerTypeId);
                }
            })
            ->when($gradeId, function ($query) use ($gradeId) {
                $query->where('grade_ID', $gradeId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhereHas('application', function ($appQuery) use ($search) {
                            $appQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom !== '' && $dateTo !== '', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
            })
            ->latest('created_at');

        $enrollments = $enrollmentsQuery->paginate($perPage)->withQueryString();

        $sections = Section::query()
            ->with('cluster')
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->where('SY_ID', $activeYear->SY_ID);
            })
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->get();

        $gradeLevels = GradeLevel::options();

        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();

        return view('users.guidance.enrollments.index', [
            'activeYear' => $activeYear,
            'enrollments' => $enrollments,
            'sections' => $sections,
            'gradeLevels' => $gradeLevels,
            'academicYears' => $academicYears,
        ]);
    }

    public function approve(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(EnrollmentStatus::activeSlugs())],
        ]);

        $result = $this->changeEnrollmentStatus($enrollment, $validated['status'], $request);

        $this->notifyEnrollmentStatus($enrollment, $validated['status']);

        return $this->redirectToEnrollmentShow($enrollment, $request, [
            'enrollment_result' => [
                'type' => 'single',
                'updated' => 1,
                'skipped' => 0,
                'failed' => 0,
                'status' => $validated['status'],
                'section_name' => $result['section']?->name,
                'student_name' => $this->enrollmentStudentDisplayName($enrollment),
                'accounts' => $result['account'] !== null ? [$result['account']] : [],
                'created_sections' => $result['created_section'] && $result['section'] ? [$result['section']->name] : [],
            ],
        ]);
    }

    public function confirmEnrollment(Request $request, Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->enrollment_status !== EnrollmentStatus::TEMPORARILY_ENROLLED) {
            return back()->withErrors([
                'status' => 'Only temporarily enrolled students can be marked as enrolled.',
            ]);
        }

        $result = $this->changeEnrollmentStatus($enrollment, EnrollmentStatus::ENROLLED, $request);

        $this->notifyEnrollmentStatus($enrollment, EnrollmentStatus::ENROLLED);

        return $this->redirectToEnrollmentShow($enrollment, $request, [
            'enrollment_result' => [
                'type' => 'single',
                'updated' => 1,
                'skipped' => 0,
                'failed' => 0,
                'status' => EnrollmentStatus::ENROLLED,
                'section_name' => $result['section']?->name,
                'student_name' => $this->enrollmentStudentDisplayName($enrollment),
                'accounts' => $result['account'] !== null ? [$result['account']] : [],
            ],
        ]);
    }

    public function updateStatus(UpdateEnrollmentStatusRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $status = $request->validated('status');

        if ($enrollment->enrollment_status === $status) {
            return $this->redirectToEnrollmentShow($enrollment, $request, [
                'status' => 'Enrollment is already '.$enrollment->enrollment_status_label.'.',
            ]);
        }

        $this->changeEnrollmentStatus($enrollment, $status, $request);
        $enrollment->refresh()->loadMissing('enrollmentStatus');
        $this->notifyEnrollmentStatus($enrollment, $status);

        return $this->redirectToEnrollmentShow($enrollment, $request, [
            'success' => 'Enrollment status updated to '.$enrollment->enrollment_status_label.'.',
        ]);
    }

    public function bulkApprove(BulkUpdateEnrollmentStatusRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $enrollments = Enrollment::query()
            ->with(['section'])
            ->whereIn('enrollment_ID', $validated['enrollment_ids'])
            ->get();

        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $accounts = [];
        $createdSections = [];

        foreach ($enrollments as $enrollment) {
            if ($enrollment->enrollment_status === $validated['status']) {
                $skipped++;

                continue;
            }

            try {
                $result = $this->changeEnrollmentStatus($enrollment, $validated['status'], $request);
            } catch (ValidationException) {
                $failed++;

                continue;
            }

            if ($result['account'] !== null) {
                $accounts[] = $result['account'];
            }

            if ($result['created_section'] && $result['section']) {
                $createdSections[] = $result['section']->name;
            }

            $this->notifyEnrollmentStatus($enrollment, $validated['status']);

            $updated++;
        }

        return back()->with('enrollment_result', [
            'type' => 'bulk',
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'status' => $validated['status'],
            'accounts' => $accounts,
            'created_sections' => array_values(array_unique($createdSections)),
        ]);
    }

    public function updatePlacementTestRecommendation(UpdatePlacementStatusRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $previousStatus = $enrollment->placement_status;

        $enrollment->update([
            'placement_status' => $request->validated('placement_status'),
        ]);

        $enrollment->load(['student', 'academicYear', 'gradeLevel']);
        StudentPlacementTestNotifier::sendIfNewlyRecommended($enrollment, $previousStatus);

        return $this->redirectToEnrollmentShow($enrollment, $request, [
            'success' => 'Placement test status updated to '.$enrollment->placement_status_label.'.',
        ]);
    }

    public function print(Enrollment $enrollment): View
    {
        $enrollment->load([
            'student.application',
            'student.profile',
            'student.guardians',
            'student.addresses',
            'cluster',
            'preferredCourse',
            'academicYear',
            'gradeLevel',
            'section',
        ]);

        return view('users.guidance.enrollments.print', [
            'enrollment' => $enrollment,
        ]);
    }

    public function printMultiple(Request $request): View
    {
        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array'],
            'enrollment_ids.*' => ['integer', 'exists:enrollments,enrollment_ID'],
        ]);

        $enrollments = Enrollment::query()
            ->with([
                'student.application',
                'student.profile',
                'student.guardians',
                'student.addresses',
                'cluster',
                'preferredCourse',
                'academicYear',
                'gradeLevel',
                'section',
            ])
            ->whereIn('enrollment_ID', $validated['enrollment_ids'])
            ->orderBy('created_at')
            ->get();

        return view('users.guidance.enrollments.print-multiple', [
            'enrollments' => $enrollments,
        ]);
    }

    public function sectioning(): View
    {
        return view('users.guidance.sectioning');
    }

    public function ageForGradeReport(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $alignment = $request->string('alignment', 'overage')->toString();
        $search = trim($request->string('search')->toString());
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $academicYearId = $request->string('academic_year_id')->toString();
        $perPage = (int) $request->integer('per_page', 15);
        $page = LengthAwarePaginator::resolveCurrentPage();

        $enrollments = Enrollment::query()
            ->with(['student', 'gradeLevel', 'section', 'academicYear', 'placementStatus'])
            ->whereIn('enrollment_status_ID', EnrollmentStatus::inProgressIds())
            ->when($academicYearId !== '' && $academicYearId !== 'all', function ($query) use ($academicYearId) {
                $query->where('SY_ID', $academicYearId);
            })
            ->when($academicYearId === '' && $activeYear, function ($query) use ($activeYear) {
                $query->where('SY_ID', $activeYear->SY_ID);
            })
            ->when($gradeId, function ($query) use ($gradeId) {
                $query->where('grade_ID', $gradeId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('grade_ID')
            ->orderBy('section_ID')
            ->orderBy('enrollment_ID')
            ->get();

        $reportRows = $enrollments
            ->map(function (Enrollment $enrollment) {
                $assessment = PlacementAssessmentAdvisor::assessmentForEnrollment($enrollment);

                if (! $assessment) {
                    return null;
                }

                return [
                    'enrollment' => $enrollment,
                    'student' => $enrollment->student,
                    'assessment' => $assessment,
                    'recommendation' => PlacementAssessmentAdvisor::forEnrollment($enrollment),
                ];
            })
            ->filter()
            ->values();

        $ageAlignment = PlacementAssessmentAdvisor::summarizeAgeAlignment(
            $reportRows->pluck('enrollment'),
        );

        $summary = [
            'all' => $ageAlignment['reviewed'],
            'appropriate' => $ageAlignment['appropriate'],
            'overage' => $ageAlignment['overage'],
            'underage' => $ageAlignment['underage'],
            'average_age' => $ageAlignment['average_age'],
            'marked_for_test' => $reportRows->filter(
                fn (array $row): bool => $row['enrollment']->isPlacementRecommended(),
            )->count(),
        ];

        $filteredRows = $alignment !== '' && $alignment !== 'all'
            ? $reportRows->where('assessment.status', $alignment)->values()
            : $reportRows;

        $paginatedRows = new LengthAwarePaginator(
            $filteredRows->forPage($page, max(1, $perPage))->values(),
            $filteredRows->count(),
            max(1, $perPage),
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('users.guidance.reports.age-for-grade', [
            'activeYear' => $activeYear,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'gradeLevels' => GradeLevel::options(),
            'rows' => $paginatedRows,
            'summary' => $summary,
            'ageAlignment' => $ageAlignment,
            'alignment' => $alignment,
            'gradeLevel' => $gradeLevel,
        ]);
    }

    public function verifyDocument(StudentDocument $document, Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $document->update([
                'status' => 'verified',
                'date_verified' => now(),
                'verified_by' => $user->staff_id,
                'return_reason_ID' => null,
            ]);

            $document->loadMissing('student');
            $document->student?->notify(new DocumentStatusUpdated($document));
            $promoted = $document->student
                ? EnrollmentDocumentCompletion::promoteWhenReady($document->student, $user)
                : null;

            $message = 'Document verified successfully.';
            if ($promoted === EnrollmentStatus::ENROLLED) {
                $message = 'Document verified successfully. Required documents are complete, so the student is now enrolled.';
            }

            return $this->redirectToEnrollmentDocuments($document, $request, [
                'success' => $message,
            ]);
        } catch (\Exception $e) {
            return $this->redirectToEnrollmentDocuments($document, $request, [])
                ->withErrors(['error' => 'Failed to verify document: '.$e->getMessage()]);
        }
    }

    public function bulkVerifyDocuments(BulkVerifyStudentDocumentsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $enrollment = Enrollment::query()->findOrFail($validated['enrollment_ID']);

        $documents = StudentDocument::query()
            ->with('student')
            ->whereIn('doc_ID', $validated['document_ids'])
            ->where('student_ID', $enrollment->student_ID)
            ->where('status', 'pending')
            ->get();

        if ($documents->isEmpty()) {
            return $this->redirectToEnrollmentShow($enrollment, $request, [], 'documents')
                ->withErrors(['error' => 'Select at least one pending document to verify.']);
        }

        foreach ($documents as $document) {
            $document->update([
                'status' => 'verified',
                'date_verified' => now(),
                'verified_by' => $user->staff_id,
                'return_reason_ID' => null,
            ]);
            $document->student?->notify(new DocumentStatusUpdated($document));
        }

        $promoted = false;
        $student = $enrollment->student ?: $documents->first()?->student;
        if ($student) {
            $promoted = EnrollmentDocumentCompletion::promoteWhenReady($student, $user) === EnrollmentStatus::ENROLLED;
        }

        $message = $documents->count() === 1
            ? 'Document verified successfully.'
            : $documents->count().' documents verified successfully.';

        if ($promoted) {
            $message .= ' Required documents are complete, so the student is now enrolled.';
        }

        return $this->redirectToEnrollmentShow($enrollment, $request, [
            'success' => $message,
        ], 'documents');
    }

    public function unverifyDocument(StudentDocument $document, Request $request): RedirectResponse
    {
        if (! $document->isVerified()) {
            return $this->redirectToEnrollmentDocuments($document, $request, [])
                ->withErrors(['error' => 'Only verified documents can be unverified.']);
        }

        try {
            $document->update([
                'status' => 'pending',
                'date_verified' => null,
                'verified_by' => null,
                'return_reason_ID' => null,
            ]);
            $document->loadMissing('student');
            $document->student?->notify(new DocumentStatusUpdated($document));

            return $this->redirectToEnrollmentDocuments($document, $request, [
                'success' => 'Document unverified. The student can now replace it.',
            ]);
        } catch (\Exception $e) {
            return $this->redirectToEnrollmentDocuments($document, $request, [])
                ->withErrors(['error' => 'Failed to unverify document: '.$e->getMessage()]);
        }
    }

    public function rejectDocument(RejectStudentDocumentRequest $request, StudentDocument $document): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            $document->update([
                'status' => DocumentStatus::RETURNED,
                'date_verified' => now(),
                'verified_by' => $user->staff_id,
                'return_reason_ID' => $validated['return_reason_ID'],
            ]);
            $document->loadMissing('student');
            $document->student?->notify(new DocumentStatusUpdated($document));

            return $this->redirectToEnrollmentDocuments($document, $request, [
                'success' => 'Document returned for resubmission.',
            ]);
        } catch (\Exception $e) {
            return $this->redirectToEnrollmentDocuments($document, $request, [])
                ->withErrors(['error' => 'Failed to return document: '.$e->getMessage()]);
        }
    }

    public function viewDocument(StudentDocument $document): StreamedResponse|RedirectResponse
    {
        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            return back()->withErrors(['error' => 'Document file was not found.']);
        }

        return Storage::disk('public')->response($document->file_path);
    }

    public function sectionsIndex(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $clusterId = $request->string('cluster_id')->toString();
        $perPage = (int) $request->integer('per_page', 16);

        $sectionsQuery = Section::query()
            ->with(['cluster', 'gradeLevel', 'adviser'])
            ->withCount(['enrollments as enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID))
            ->when($gradeId, fn ($q) => $q->where('grade_ID', $gradeId))
            ->when($clusterId !== '', fn ($q) => $q->where('cluster_ID', $clusterId))
            ->orderBy('grade_ID')
            ->orderBy('name');

        $sections = $sectionsQuery->paginate($perPage)->withQueryString();

        $gradeLevels = GradeLevel::options();

        $clusters = Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);
        $academicYears = AcademicYear::query()->orderByDesc('SY_ID')->get(['SY_ID', 'school_year', 'status']);
        $staffs = Staff::query()
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['staff_id', 'first_name', 'middle_name', 'last_name', 'suffix']);

        $showClusterColumn = in_array($gradeLevel, ['grade_11', 'grade_12'], true);

        return view('users.guidance.sections.index', [
            'activeYear' => $activeYear,
            'sections' => $sections,
            'gradeLevels' => $gradeLevels,
            'clusters' => $clusters,
            'academicYears' => $academicYears,
            'staffs' => $staffs,
            'showClusterFilter' => $showClusterColumn,
            'showClusterColumn' => $showClusterColumn,
        ]);
    }

    public function storeSection(StoreSectionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['grade_ID'] = GradeLevel::idForValue($validated['grade_level']);
        $isSeniorHigh = in_array($validated['grade_level'], ['grade_11', 'grade_12'], true);

        if (! $isSeniorHigh) {
            $validated['cluster_ID'] = null;
        }

        $curriculumId = VacantSectionAssigner::curriculumIdFor($validated['grade_level'], $validated['cluster_ID'] ?? null);

        if ($curriculumId === null) {
            return back()->withErrors(['grade_level' => 'Unable to determine the curriculum for this section. Contact an administrator.'])->withInput();
        }

        $validated['curriculum_ID'] = $curriculumId;

        unset($validated['grade_level']);

        Section::query()->create($validated);

        return back()->with('success', 'Section created successfully.');
    }

    public function sectionsShow(Section $section): View
    {
        $section->load([
            'cluster',
            'gradeLevel',
            'adviser',
            'enrollments.student.application',
            'enrollments.enrollmentStatus',
        ]);

        $targetSections = Section::query()
            ->with('cluster')
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->where('SY_ID', $section->SY_ID)
            ->where('grade_ID', $section->grade_ID)
            ->where('section_ID', '!=', $section->section_ID)
            ->orderBy('name')
            ->get();

        $staffs = Staff::query()
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['staff_id', 'first_name', 'middle_name', 'last_name', 'suffix']);

        $clusters = Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);

        return view('users.guidance.sections.show', [
            'section' => $section,
            'targetSections' => $targetSections,
            'staffs' => $staffs,
            'clusters' => $clusters,
        ]);
    }

    public function updateSection(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'room' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'digits_between:1,3', 'min:1', 'max:100'],
            'staff_ID' => ['nullable', 'integer', Rule::exists('staffs', 'staff_id')],
            'cluster_ID' => ['nullable', 'integer', Rule::exists('clusters', 'cluster_ID')],
        ], [
            'capacity.integer' => 'Capacity must be a number.',
            'capacity.digits_between' => 'Capacity can be at most 3 digits.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot be more than 100.',
        ]);

        $gradeLevel = $section->grade_level;
        $isSeniorHigh = in_array($gradeLevel, ['grade_11', 'grade_12'], true);

        if ($isSeniorHigh && empty($validated['cluster_ID'])) {
            return back()->withErrors(['cluster_ID' => 'Cluster is required for Grade 11 and Grade 12 sections.'])->withInput();
        }

        if (! $isSeniorHigh) {
            $validated['cluster_ID'] = null;
        }

        $activeCount = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->count();

        if ((int) $validated['capacity'] < $activeCount) {
            return back()
                ->withInput()
                ->withErrors([
                    'capacity' => "Capacity cannot be lower than the {$activeCount} active student(s) currently in this section.",
                ]);
        }

        $curriculumId = VacantSectionAssigner::curriculumIdFor($gradeLevel, $validated['cluster_ID'] ?? null);

        if ($curriculumId === null) {
            return back()->withErrors(['cluster_ID' => 'Unable to determine the curriculum for this section. Contact an administrator.'])->withInput();
        }

        $section->update([
            'room' => $validated['room'] ?: null,
            'capacity' => (int) $validated['capacity'],
            'staff_ID' => $validated['staff_ID'] ?? null,
            'cluster_ID' => $validated['cluster_ID'] ?? null,
            'curriculum_ID' => $curriculumId,
        ]);

        return back()->with('status', 'Section details updated successfully.');
    }

    public function updateSectionCapacity(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'capacity' => ['required', 'integer', 'digits_between:1,3', 'min:1', 'max:100'],
        ], [
            'capacity.integer' => 'Capacity must be a number.',
            'capacity.digits_between' => 'Capacity can be at most 3 digits.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot be more than 100.',
        ]);

        $activeCount = Enrollment::query()
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->count();

        if ((int) $validated['capacity'] < $activeCount) {
            return back()
                ->withInput()
                ->withErrors([
                    'capacity' => "Capacity cannot be lower than the {$activeCount} active student(s) currently in this section.",
                ]);
        }

        $section->update([
            'capacity' => (int) $validated['capacity'],
        ]);

        return back()->with('status', "Maximum capacity updated to {$validated['capacity']}.");
    }

    public function transferSectionStudents(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'target_section_id' => ['required', 'integer', 'exists:sections,section_ID'],
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', 'exists:enrollments,enrollment_ID'],
        ]);

        $targetSection = Section::query()
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->where('section_ID', $validated['target_section_id'])
            ->where('SY_ID', $section->SY_ID)
            ->where('grade_ID', $section->grade_ID)
            ->where('section_ID', '!=', $section->section_ID)
            ->first();

        if (! $targetSection) {
            return back()
                ->withInput()
                ->withErrors(['target_section_id' => 'Choose another section from the same school year and grade level.']);
        }

        $enrollments = Enrollment::query()
            ->whereIn('enrollment_ID', $validated['enrollment_ids'])
            ->where('section_ID', $section->section_ID)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->get();

        if ($enrollments->count() !== count(array_unique($validated['enrollment_ids']))) {
            return back()
                ->withInput()
                ->withErrors(['enrollment_ids' => 'Only active students from this section can be transferred.']);
        }

        $capacity = (int) $targetSection->capacity;
        $currentCount = (int) $targetSection->active_enrollments_count;
        $availableSlots = $capacity === 0 ? PHP_INT_MAX : max(0, $capacity - $currentCount);

        if ($enrollments->count() > $availableSlots) {
            return back()
                ->withInput()
                ->withErrors([
                    'target_section_id' => "The target section only has {$availableSlots} available slot(s).",
                ]);
        }

        DB::transaction(function () use ($enrollments, $targetSection): void {
            foreach ($enrollments as $enrollment) {
                $enrollment->update([
                    'section_ID' => $targetSection->section_ID,
                    'cluster_ID' => $targetSection->cluster_ID,
                ]);
            }
        });

        $count = $enrollments->count();

        return redirect()
            ->route('guidance.sections.show', $section)
            ->with('status', "{$count} student(s) transferred to {$targetSection->name}.");
    }

    public function sectionsClassList(Section $section): View
    {
        $section->load([
            'cluster',
            'academicYear',
            'enrollments.student.application',
            'enrollments.enrollmentStatus',
        ]);

        return view('users.guidance.sections.class-list', [
            'section' => $section,
        ]);
    }

    public function sectionsMasterList(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $gradeLevel = $request->string('grade_level')->toString();
        $gradeId = GradeLevel::idForValue($gradeLevel);
        $clusterId = $request->string('cluster_id')->toString();

        $sections = Section::query()
            ->with([
                'cluster',
                'gradeLevel',
                'adviser',
                'academicYear',
                'enrollments.gradeLevel',
                'enrollments.student.application',
                'enrollments.enrollmentStatus',
                'enrollments.student.profile',
                'enrollments.student.guardians',
                'enrollments.student.addresses',
            ])
            ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID))
            ->when($gradeId, fn ($q) => $q->where('grade_ID', $gradeId))
            ->when($clusterId !== '', fn ($q) => $q->where('cluster_ID', $clusterId))
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->get();

        return view('users.guidance.sections.master-list', [
            'activeYear' => $activeYear,
            'sections' => $sections,
        ]);
    }

    public function show(Request $request, Enrollment $enrollment): View
    {
        $enrollment->load([
            'student.application',
            'student.profile',
            'student.guardians',
            'student.addresses',
            'cluster',
            'preferredCourse',
            'gradeLevel',
            'academicYear',
            'section.gradeLevel',
            'gradeLevel',
            'enrollmentStatus',
            'learnerType',
            'placementStatus',
        ]);

        // Get student documents
        $documents = StudentDocument::query()
            ->with('returnReason')
            ->where('student_ID', $enrollment->student_ID)
            ->orderBy('created_at', 'desc')
            ->get();

        $syId = $enrollment->SY_ID;
        $sections = Section::query()
            ->with('cluster')
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->when($syId, function ($query) use ($syId) {
                $query->where('SY_ID', $syId);
            })
            ->where('grade_ID', $enrollment->grade_ID)
            ->when($enrollment->cluster_ID, function ($query) use ($enrollment) {
                $query->where('cluster_ID', $enrollment->cluster_ID);
            }, function ($query) {
                $query->whereNull('cluster_ID');
            })
            ->orderBy('grade_ID')
            ->orderBy('name')
            ->get();

        $returnSection = null;
        if ($request->filled('from_section')) {
            $returnSection = Section::query()->find($request->integer('from_section'));
        }

        return view('users.guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'documents' => $documents,
            'documentReturnReasons' => DocumentReturnReason::query()->orderBy('name')->get(),
            'sections' => $sections,
            'returnSection' => $returnSection,
            'fromSectionId' => $request->input('from_section'),
            'activeStep' => $request->string('step')->toString(),
        ]);
    }

    private function redirectToEnrollmentShow(Enrollment $enrollment, Request $request, array $session = [], ?string $step = null): RedirectResponse
    {
        $parameters = ['enrollment' => $enrollment];

        if ($request->filled('from_section')) {
            $parameters['from_section'] = $request->input('from_section');
        }

        if ($step) {
            $parameters['step'] = $step;
        } elseif (in_array($request->string('step')->toString(), ['enrollment', 'personal', 'addresses', 'parents', 'documents'], true)) {
            $parameters['step'] = $request->string('step')->toString();
        }

        return redirect()
            ->route('guidance.enrollments.show', $parameters)
            ->with($session);
    }

    private function redirectToEnrollmentDocuments(StudentDocument $document, Request $request, array $session = []): RedirectResponse
    {
        $enrollment = Enrollment::query()
            ->where('student_ID', $document->student_ID)
            ->latest('created_at')
            ->first();

        if (! $enrollment) {
            return back()->with($session);
        }

        return $this->redirectToEnrollmentShow($enrollment, $request, $session, 'documents');
    }

    private function notifyEnrollmentStatus(Enrollment $enrollment, string $status): void
    {
        $enrollment->loadMissing(['student', 'academicYear']);
        $student = $enrollment->student;

        if (! $student) {
            return;
        }

        StudentEnrollmentNotifier::send($student, $enrollment, $status);
    }

    private function enrollmentStudentDisplayName(Enrollment $enrollment): string
    {
        $enrollment->loadMissing('student.application');
        $student = $enrollment->student;

        if (! $student) {
            return 'Student';
        }

        $application = $student->application;
        $firstName = $application?->first_name ?? $student->first_name;
        $lastName = $application?->last_name ?? $student->last_name;
        $middleName = $application?->middle_name ?? $student->middle_name;
        $name = trim(($lastName ? $lastName.', ' : '').($firstName ?? '').($middleName ? ' '.$middleName : ''));

        return $name !== '' ? $name : 'Student';
    }

    /**
     * @return array{account: array{student_name: string, username: string, password: string}|null, section: ?Section, created_section: bool}
     */
    private function changeEnrollmentStatus(Enrollment $enrollment, string $status, Request $request): array
    {
        $account = null;
        $section = $enrollment->section;
        $createdSection = false;

        DB::transaction(function () use ($request, $enrollment, $status, &$account, &$section, &$createdSection): void {
            $payload = [
                'enrollment_status' => $status,
            ];

            if (in_array($status, EnrollmentStatus::activeSlugs(), true) && ! $enrollment->section_ID) {
                $section = VacantSectionAssigner::resolve($enrollment);

                if (! $section) {
                    throw ValidationException::withMessages([
                        'status' => 'Unable to assign or create a section for this enrollment.',
                    ]);
                }

                $payload['section_ID'] = $section->section_ID;
                $createdSection = $section->wasRecentlyCreated;
            }

            $enrollment->update($payload);

            if (in_array($status, EnrollmentStatus::activeSlugs(), true)) {
                $account = $this->ensureStudentAccount($enrollment, $request);
            }
        });

        $enrollment->loadMissing('section');

        return [
            'account' => $account,
            'section' => $section ?? $enrollment->section,
            'created_section' => $createdSection,
        ];
    }

    /**
     * @return array{student_name: string, username: string, password: string}|null
     */
    private function ensureStudentAccount(Enrollment $enrollment, Request $request): ?array
    {
        return StudentAccountProvisioner::ensure($enrollment, $request->user());
    }
}
