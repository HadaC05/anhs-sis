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
use App\Models\Curriculum;
use App\Models\DocumentReturnReason;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\LearnerType;
use App\Models\PlacementStatus;
use App\Models\PromotionStatus;
use App\Models\Room;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StudentDocument;
use App\Notifications\DocumentStatusUpdated;
use App\Support\EnrollmentDashboardData;
use App\Support\EnrollmentDocumentCompletion;
use App\Support\PlacementAssessmentAdvisor;
use App\Support\PromotionEligibility;
use App\Support\PromotionRegistrar;
use App\Support\RetainedEnrollmentRegistrar;
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
    public function promotions(Request $request): View
    {
        [$query, $eligibility, $tab] = $this->promotionQuery($request);
        $isPrincipal = $request->routeIs('principal.*');
        $enrollments = $query->latest('SY_ID')->orderBy('enrollment_ID')->paginate(15)->withQueryString();
        $activeAcademicYear = AcademicYear::query()->where('status', true)->first();
        $activeYearEnrollmentStudentIds = $activeAcademicYear
            ? Enrollment::query()
                ->where('SY_ID', $activeAcademicYear->SY_ID)
                ->whereIn('student_ID', $enrollments->pluck('student_ID'))
                ->pluck('student_ID')
                ->map(fn (mixed $studentId): int => (int) $studentId)
                ->all()
            : [];

        return view('users.guidance.promotions.index', [
            'isPrincipal' => $isPrincipal,
            'enrollments' => $enrollments,
            'gradeLevels' => GradeLevel::query()->orderBy('grade_ID')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'activeAcademicYear' => $activeAcademicYear,
            'activeYearEnrollmentStudentIds' => $activeYearEnrollmentStudentIds,
            'eligibility' => $eligibility,
            'tab' => $tab,
        ]);
    }

    private function promotionQuery(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'grade_level' => ['nullable', 'integer', Rule::exists(GradeLevel::class, 'grade_ID')],
            'eligibility' => ['nullable', Rule::in(['all', ...array_column(PromotionStatus::definitions(), 'slug')])],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,SY_ID'],
            'tab' => ['nullable', Rule::in(['promotions', 'remediation'])],
        ]);
        $tab = $request->routeIs('principal.*') && ($filters['tab'] ?? null) === 'remediation'
            ? 'remediation'
            : 'promotions';
        $eligibility = $filters['eligibility'] ?? ($request->routeIs('principal.*') ? 'all' : PromotionStatus::ELIGIBLE);
        $query = Enrollment::query()
            ->with(['student.application', 'gradeLevel', 'academicYear', 'promotionStatus', 'remediationCase.subjects'])
            ->when($filters['grade_level'] ?? null, fn ($query, $grade) => $query->whereHas('curriculumGradeLevel', fn ($query) => $query->where('grade_ID', $grade)))
            ->when($filters['academic_year_id'] ?? null, fn ($query, $year) => $query->where('SY_ID', $year))
            ->when($tab === 'remediation', fn ($query) => $query->whereHas('remediationCase'));

        foreach (preg_split('/[\s,]+/', trim($filters['search'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->whereHas('student', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $like = '%'.$term.'%';
                    $query->where('lrn', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('middle_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhereHas('application', fn ($query) => $query
                            ->where('first_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like));
                });
            });
        }

        (clone $query)->whereNotNull('section_ID')
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->where('promotion_status_ID', '!=', PromotionStatus::idFor(PromotionStatus::PROMOTED))
            ->chunkById(250, fn ($enrollments) => PromotionEligibility::synchronizeMany($enrollments), 'enrollment_ID');
        $query->when($eligibility !== 'all', fn ($query) => $query->where('promotion_status_ID', PromotionStatus::idFor($eligibility)));

        return [$query, $eligibility, $tab];
    }

    public function downloadPromotionSf5(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        [$query] = $this->promotionQuery($request);

        return \App\Support\Sf5Export::download($query->get());
    }

    public function bulkPromote(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1', 'max:100'],
            'enrollment_ids.*' => ['required', 'integer', 'distinct', 'exists:enrollments,enrollment_ID'],
        ]);
        $enrollments = Enrollment::query()->whereIn('enrollment_ID', $validated['enrollment_ids'])->get();
        $completed = 0;

        DB::transaction(function () use ($enrollments, &$completed): void {
            foreach ($enrollments as $enrollment) {
                try {
                    if (PromotionRegistrar::promote($enrollment, requireActiveYear: true) === null) {
                        $completed++;
                    }
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages([
                        'promotion' => 'No learners were promoted. '.($exception->errors()['promotion'][0] ?? 'A selected learner could not be promoted.').' Review your selection and try again.',
                    ]);
                }
            }
        });

        $promoted = $enrollments->count() - $completed;
        $message = match (true) {
            $completed === 0 => "{$promoted} selected learner(s) promoted to the next active school year.",
            $promoted === 0 => "{$completed} selected Grade 10 learner(s) marked as having completed Junior High School; no Grade 11 enrollments were created.",
            default => "{$promoted} learner(s) promoted and {$completed} Grade 10 learner(s) marked as having completed Junior High School.",
        };

        return back()->with('status', $message);
    }

    public function confirmPromotion(Enrollment $enrollment): RedirectResponse
    {
        $nextEnrollment = DB::transaction(
            fn (): ?Enrollment => PromotionRegistrar::promote($enrollment, requireActiveYear: true),
        );

        if (! $nextEnrollment) {
            return redirect()->route(\App\Support\AcademicPortal::routeName('guidance.promotions.index'))
                ->with('success', 'Junior High School completion confirmed. No Grade 11 enrollment was created.');
        }

        $nextGradeLabel = $nextEnrollment->gradeLevel()->value('grade_label') ?? 'next-grade';

        return redirect()->route(\App\Support\AcademicPortal::routeName('guidance.enrollments.show'), $nextEnrollment)
            ->with('success', "Promotion confirmed. A pending {$nextGradeLabel} enrollment was created for {$nextEnrollment->academicYear?->school_year}; assign the learner to a section to finish enrollment.");
    }

    public function reenrollRetained(Enrollment $enrollment): RedirectResponse
    {
        $repeatEnrollment = DB::transaction(
            fn (): Enrollment => RetainedEnrollmentRegistrar::enroll($enrollment),
        );
        $gradeLabel = $repeatEnrollment->gradeLevel()->value('grade_label') ?? 'same-grade';

        return redirect()->route(\App\Support\AcademicPortal::routeName('guidance.enrollments.show'), $repeatEnrollment)
            ->with('success', "A pending {$gradeLabel} repeat enrollment was created for {$repeatEnrollment->academicYear?->school_year}. The learner remains retained in the prior school year.");
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
                'cluster.track',
                'track',
                'electives',
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
                $query->forGrade($gradeId);
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
            'cluster.track',
            'track',
            'electives',
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
                'cluster.track',
                'track',
                'electives',
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
                $query->forGrade($gradeId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->orderByGrade()
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
                ];
            })
            ->filter()
            ->values();

        $ageAlignment = PlacementAssessmentAdvisor::summarizeAgeAlignment(
            $reportRows->pluck('enrollment'),
        );

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
            'ageAlignment' => $ageAlignment,
            'alignment' => $alignment,
            'gradeLevel' => $gradeLevel,
        ]);
    }

    public function downloadPlacementTestRecommendations(Request $request): StreamedResponse
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $search = trim($request->string('search')->toString());
        $gradeId = GradeLevel::idForValue($request->string('grade_level')->toString());
        $academicYearId = $request->string('academic_year_id')->toString();

        $enrollments = Enrollment::query()
            ->with(['student', 'gradeLevel', 'section', 'academicYear', 'placementStatus'])
            ->whereIn('enrollment_status_ID', EnrollmentStatus::inProgressIds())
            ->whereHas('placementStatus', fn ($query) => $query->where('slug', PlacementStatus::RECOMMENDED))
            ->when($academicYearId !== '' && $academicYearId !== 'all', function ($query) use ($academicYearId) {
                $query->where('SY_ID', $academicYearId);
            })
            ->when($academicYearId === '' && $activeYear, function ($query) use ($activeYear) {
                $query->where('SY_ID', $activeYear->SY_ID);
            })
            ->when($gradeId, function ($query) use ($gradeId) {
                $query->forGrade($gradeId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                });
            })
            ->orderByGrade()
            ->orderBy('section_ID')
            ->orderBy('enrollment_ID')
            ->get();

        $filename = 'placement-test-recommendations-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($enrollments): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Student', 'LRN', 'School Year', 'Grade Level', 'Section', 'Age', 'Expected Age Range', 'Placement Test Status']);

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $assessment = PlacementAssessmentAdvisor::assessmentForEnrollment($enrollment);

                fputcsv($output, [
                    trim(($student?->last_name ?? '').', '.($student?->first_name ?? '').' '.($student?->middle_name ?? '')),
                    $student?->lrn ?? '',
                    $enrollment->academicYear?->school_year ?? '',
                    $enrollment->grade_level ? GradeLevel::valueToLabel($enrollment->grade_level) : '',
                    $enrollment->section?->name ?? 'Unassigned',
                    $assessment['age'] ?? '',
                    $assessment ? "{$assessment['minimum_age']}-{$assessment['maximum_age']}" : '',
                    $enrollment->placement_status_label,
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function verifyDocument(StudentDocument $document, Request $request): RedirectResponse
    {
        if ($document->doc_type === DocumentType::ID_PHOTO) {
            return $this->redirectToEnrollmentDocuments($document, $request, [])
                ->withErrors(['error' => 'The 2x2 photo is a profile photo and does not require document verification.']);
        }

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
            ->where('doc_type', '!=', DocumentType::ID_PHOTO)
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
        if ($document->doc_type === DocumentType::ID_PHOTO) {
            return $this->redirectToEnrollmentDocuments($document, $request, [])
                ->withErrors(['error' => 'The 2x2 photo is a profile photo and does not require document verification.']);
        }

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
        if ($document->doc_type === DocumentType::ID_PHOTO) {
            return $this->redirectToEnrollmentDocuments($document, $request, [])
                ->withErrors(['error' => 'The 2x2 photo is a profile photo and does not require document verification.']);
        }

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
            'rooms' => Room::query()->orderBy('name')->get(['name']),
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

        $isSeniorHigh = in_array($validated['grade_level'], ['grade_11', 'grade_12'], true);

        if (! $isSeniorHigh) {
            $validated['cluster_ID'] = null;
        }

        $curriculumId = VacantSectionAssigner::curriculumIdFor($validated['grade_level'], $validated['cluster_ID'] ?? null);

        if ($curriculumId === null) {
            return back()->withErrors(['grade_level' => 'Unable to determine the curriculum for this section. Contact an administrator.'])->withInput();
        }

        $validated['curriculum_grade_level_ID'] = $curriculumId;
        $offering = Curriculum::query()->findOrFail($curriculumId);
        $validated['grade_ID'] = $offering->grade_ID;
        $validated['cluster_ID'] = $offering->cluster_ID;

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
            'rooms' => Room::query()->orderBy('name')->get(['name']),
            'section' => $section,
            'targetSections' => $targetSections,
            'staffs' => $staffs,
            'clusters' => $clusters,
        ]);
    }

    public function updateSection(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'room' => ['nullable', 'string', 'max:255', Rule::exists('rooms', 'name')],
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
            'curriculum_grade_level_ID' => $curriculumId,
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
            'cluster.track',
            'track',
            'electives',
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
            ->where('doc_type', '!=', DocumentType::ID_PHOTO)
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
            ->route(\App\Support\AcademicPortal::routeName('guidance.enrollments.show'), $parameters)
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
