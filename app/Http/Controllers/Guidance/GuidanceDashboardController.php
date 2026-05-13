<?php

namespace App\Http\Controllers\Guidance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\StudentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class GuidanceDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = AcademicYear::query()->where('status', true)->first();
        $baseQuery = Enrollment::query()
            ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID));

        $totalCount = (clone $baseQuery)->count();
        $pendingCount = (clone $baseQuery)->where('enrollment_status', 'pending')->count();
        $enrolledCount = (clone $baseQuery)->where('enrollment_status', 'enrolled')->count();
        $temporaryCount = (clone $baseQuery)->where('enrollment_status', 'temporarily_enrolled')->count();
        $approvedCount = $enrolledCount + $temporaryCount;
        $recentEnrollments = (clone $baseQuery)
            ->with(['student.application', 'section.gradeLevel', 'gradeLevel', 'cluster', 'preferredCourse'])
            ->latest('created_at')
            ->limit(5)
            ->get();
        $sectionStats = Section::query()
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
            }])
            ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID))
            ->get();
        $sectionCount = $sectionStats->count();
        $totalCapacity = $sectionStats->sum('capacity');
        $totalEnrolled = $sectionStats->sum('active_enrollments_count');

        return view('users.guidance.dashboard', [
            'activeYear' => $activeYear,
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'enrolledCount' => $enrolledCount,
            'temporaryCount' => $temporaryCount,
            'approvedCount' => $approvedCount,
            'sectionCount' => $sectionCount,
            'totalCapacity' => $totalCapacity,
            'totalEnrolled' => $totalEnrolled,
            'recentEnrollments' => $recentEnrollments,
        ]);
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
            ])
            ->when($academicYearId !== '' && $academicYearId !== 'all', function ($query) use ($academicYearId) {
                $query->where('SY_ID', $academicYearId);
            })
            ->when($academicYearId === '' && $activeYear, function ($query) use ($activeYear) {
                $query->where('SY_ID', $activeYear->SY_ID);
            })
            ->when($status !== '' && $status !== 'all', function ($query) use ($status) {
                $query->where('enrollment_status', $status);
            }, function ($query) use ($status) {
                if ($status === '') {
                    $query->where('enrollment_status', 'pending');
                }
            })
            ->when($learnerType !== '', function ($query) use ($learnerType) {
                $query->where('learner_type', $learnerType);
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
                $query->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
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
            'status' => ['required', 'in:enrolled,temporarily_enrolled'],
        ]);

        $section = $this->findFirstVacantSection($enrollment);

        if (! $section) {
            return back()->withErrors(['status' => 'No vacant section is available for this enrollment.']);
        }

        $enrollment->update([
            'section_ID' => $section->section_ID,
            'enrollment_status' => $validated['status'],
        ]);

        return back()->with('status', 'Enrollment updated and section assigned.');
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:enrolled,temporarily_enrolled'],
            'enrollment_ids' => ['required', 'array'],
            'enrollment_ids.*' => ['integer', 'exists:enrollments,enrollment_ID'],
        ]);

        $enrollments = Enrollment::query()
            ->with(['section'])
            ->whereIn('enrollment_ID', $validated['enrollment_ids'])
            ->get();

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($enrollments as $enrollment) {
            if ($enrollment->enrollment_status !== 'pending') {
                $skipped++;
                continue;
            }

            $section = $this->findFirstVacantSection($enrollment);
            if (! $section) {
                $failed++;
                continue;
            }

            $enrollment->update([
                'section_ID' => $section->section_ID,
                'enrollment_status' => $validated['status'],
            ]);

            $updated++;
        }

        $messageParts = [];
        if ($updated > 0) {
            $messageParts[] = "{$updated} enrollment(s) updated.";
        }
        if ($skipped > 0) {
            $messageParts[] = "{$skipped} skipped (not pending).";
        }
        if ($failed > 0) {
            $messageParts[] = "{$failed} failed (no vacant section).";
        }

        if (! $messageParts) {
            $messageParts[] = 'No enrollments were updated.';
        }

        return back()->with('status', implode(' ', $messageParts));
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

    public function verifyDocument(StudentDocument $document, Request $request)
    {
        $user = $request->user();
        
        try {
            $document->update([
                'status' => 'verified',
                'date_verified' => now(),
                'verified_by' => $user->staff_id,
            ]);

            return back()->with('success', 'Document verified successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to verify document: ' . $e->getMessage()]);
        }
    }

    public function rejectDocument(StudentDocument $document, Request $request)
    {
        $user = $request->user();
        
        try {
            $document->update([
                'status' => 'rejected',
                'date_verified' => now(),
                'verified_by' => $user->staff_id,
            ]);

            return back()->with('success', 'Document rejected successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reject document: ' . $e->getMessage()]);
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
        $perPage = (int) $request->integer('per_page', 15);

        $sectionsQuery = Section::query()
            ->with(['cluster', 'gradeLevel', 'adviser'])
            ->withCount(['enrollments as enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
            }])
            ->when($activeYear, fn ($q) => $q->where('SY_ID', $activeYear->SY_ID))
            ->when($gradeId, fn ($q) => $q->where('grade_ID', $gradeId))
            ->when($clusterId !== '', fn ($q) => $q->where('cluster_ID', $clusterId))
            ->orderBy('grade_ID')
            ->orderBy('name');

        $sections = $sectionsQuery->paginate($perPage)->withQueryString();

        $totalCapacity = $sections->sum('capacity');
        $totalEnrolled = $sections->sum('enrollments_count');

        $gradeLevels = GradeLevel::options();

        $clusters = \App\Models\Cluster::query()->orderBy('name')->get(['cluster_ID', 'name']);

        $showClusterColumn = in_array($gradeLevel, ['grade_11', 'grade_12'], true);

        return view('users.guidance.sections.index', [
            'activeYear' => $activeYear,
            'sections' => $sections,
            'gradeLevels' => $gradeLevels,
            'clusters' => $clusters,
            'showClusterFilter' => $showClusterColumn,
            'showClusterColumn' => $showClusterColumn,
            'totalCapacity' => $totalCapacity,
            'totalEnrolled' => $totalEnrolled,
        ]);
    }

    public function sectionsShow(Section $section): View
    {
        $section->load([
            'cluster',
            'gradeLevel',
            'adviser',
            'enrollments.student.application',
        ]);

        $targetSections = Section::query()
            ->where('SY_ID', $section->SY_ID)
            ->where('grade_ID', $section->grade_ID)
            ->where('section_ID', '!=', $section->section_ID)
            ->orderBy('name')
            ->get();

        return view('users.guidance.sections.show', [
            'section' => $section,
            'targetSections' => $targetSections,
        ]);
    }

    public function sectionsClassList(Section $section): View
    {
        $section->load([
            'cluster',
            'academicYear',
            'enrollments.student.application',
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
    public function show(Enrollment $enrollment): View
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
        ]);

        // Get student documents
        $documents = StudentDocument::query()
            ->where('student_ID', $enrollment->student_ID)
            ->orderBy('created_at', 'desc')
            ->get();

        $syId = $enrollment->SY_ID;
        $sections = Section::query()
            ->with('cluster')
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
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

        return view('users.guidance.enrollments.show', [
            'enrollment' => $enrollment,
            'documents' => $documents,
            'sections' => $sections,
        ]);
    }

    private function findFirstVacantSection(Enrollment $enrollment): ?Section
    {
        $sections = Section::query()
            ->withCount(['enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('enrollment_status', ['enrolled', 'temporarily_enrolled']);
            }])
            ->where('SY_ID', $enrollment->SY_ID)
            ->where('grade_ID', $enrollment->grade_ID)
            ->when($enrollment->cluster_ID, function ($query) use ($enrollment) {
                $query->where('cluster_ID', $enrollment->cluster_ID);
            }, function ($query) {
                $query->whereNull('cluster_ID');
            })
            ->orderBy('section_ID')
            ->get();

        foreach ($sections as $section) {
            $capacity = (int) $section->capacity;
            $current = (int) $section->active_enrollments_count;
            if ($capacity === 0 || $current < $capacity) {
                return $section;
            }
        }

        return null;
    }
}
