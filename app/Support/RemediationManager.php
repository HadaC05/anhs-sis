<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\PromotionStatus;
use App\Models\RemediationCase;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemediationManager
{
    public static function start(Enrollment $enrollment, Staff $actor): RemediationCase
    {
        $evaluation = PromotionEligibility::synchronize($enrollment);
        if ($evaluation['status'] !== PromotionStatus::CONDITIONALLY_PROMOTED) {
            throw ValidationException::withMessages([
                'remediation' => 'Remediation can only be started for a conditionally promoted learner.',
            ]);
        }

        return DB::transaction(function () use ($enrollment, $actor, $evaluation): RemediationCase {
            $case = RemediationCase::query()->firstOrCreate(
                ['enrollment_ID' => $enrollment->enrollment_ID],
                ['status' => RemediationCase::IN_PROGRESS, 'started_by' => $actor->staff_id],
            );

            if ($case->wasRecentlyCreated) {
                foreach ($evaluation['failed_subjects'] as $failedSubject) {
                    $case->subjects()->create([
                        'subject_ID' => $failedSubject['subject_id'],
                        'original_final_grade' => $failedSubject['final_grade'],
                    ]);
                }
            }

            return $case->load(['enrollment.student', 'subjects.subject', 'starter', 'approver']);
        });
    }

    public static function save(RemediationCase $case, array $validated, bool $submit): RemediationCase
    {
        if ($case->status !== RemediationCase::IN_PROGRESS) {
            throw ValidationException::withMessages([
                'remediation' => 'Only an in-progress remediation case can be edited.',
            ]);
        }

        return DB::transaction(function () use ($case, $validated, $submit): RemediationCase {
            $case->update([
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($case->subjects as $subject) {
                $input = $validated['subjects'][$subject->remediation_subject_ID] ?? [];
                $mark = isset($input['remedial_class_mark']) && $input['remedial_class_mark'] !== ''
                    ? round((float) $input['remedial_class_mark'], 2)
                    : null;
                $subject->update([
                    'remedial_class_mark' => $mark,
                    'recomputed_final_grade' => $mark === null
                        ? null
                        : round(((float) $subject->original_final_grade + $mark) / 2, 2),
                    'remarks' => $input['remarks'] ?? null,
                ]);
            }

            if ($submit) {
                $case->refresh()->load('subjects');
                if (! $case->start_date || ! $case->end_date || $case->subjects->contains(fn ($subject): bool => $subject->remedial_class_mark === null)) {
                    throw ValidationException::withMessages([
                        'remediation' => 'Complete the remediation dates and every Remedial Class Mark before submitting for approval.',
                    ]);
                }
                $case->update(['status' => RemediationCase::AWAITING_APPROVAL]);
            }

            return $case->fresh(['enrollment.student', 'subjects.subject', 'starter', 'approver']) ?? $case;
        });
    }

    public static function approve(RemediationCase $case, Staff $principal): RemediationCase
    {
        if ($case->status !== RemediationCase::AWAITING_APPROVAL) {
            throw ValidationException::withMessages([
                'remediation' => 'Only a remediation case awaiting approval can be approved.',
            ]);
        }

        return DB::transaction(function () use ($case, $principal): RemediationCase {
            $case->load('subjects');
            $passed = $case->subjects->isNotEmpty()
                && $case->subjects->every(fn ($subject): bool => (float) $subject->recomputed_final_grade >= PromotionEligibility::PASSING_GRADE);
            $case->update([
                'status' => $passed ? RemediationCase::APPROVED_PASSED : RemediationCase::NEEDS_INTERVENTION,
                'approved_by' => $principal->staff_id,
                'approved_at' => now(),
            ]);
            $case->enrollment->update([
                'promotion_status' => $passed ? PromotionStatus::ELIGIBLE : PromotionStatus::CONDITIONALLY_PROMOTED,
            ]);

            return $case->fresh(['enrollment.student', 'subjects.subject', 'starter', 'approver']) ?? $case;
        });
    }
}
