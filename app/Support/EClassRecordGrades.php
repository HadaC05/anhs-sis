<?php

namespace App\Support;

use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Collection;

class EClassRecordGrades
{
    /** Match identities, never workbook row positions; do not persist until the teacher saves. */
    public static function match(array $records, Collection $enrollments, TeacherSubjectAssignment $assignment, string $period): array
    {
        $index = [];
        $withoutMiddleName = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }
            if (blank($student->middle_name)) {
                $key = EClassRecord::normalize("{$student->last_name}, {$student->first_name}");
                $withoutMiddleName[$key][$enrollment->enrollment_ID] = true;
            }
            foreach (array_unique([$student->middle_name, mb_substr($student->middle_name ?? '', 0, 1), '']) as $middle) {
                foreach ([
                    "{$student->last_name}, {$student->first_name} {$student->suffix} {$middle}",
                    "{$student->last_name}, {$student->first_name} {$middle} {$student->suffix}",
                    "{$student->first_name} {$middle} {$student->last_name} {$student->suffix}",
                ] as $name) {
                    $index[EClassRecord::normalize($name)][$enrollment->enrollment_ID] = true;
                }
            }
        }
        $subjects = StudentSubject::query()->whereIn('enrollment_ID', $enrollments->pluck('enrollment_ID'))
            ->where('subject_ID', $assignment->subject_ID)->pluck('student_subject_ID', 'enrollment_ID');
        $locked = StudentSubjectGrade::query()->where('assignment_ID', $assignment->assignment_ID)
            ->forPeriodKey($period)->get()->filter->isTeacherLocked()->pluck('student_subject_ID')->all();
        $matched = [];
        $issues = [];
        foreach ($records as $record) {
            $ids = array_keys($index[EClassRecord::normalize($record['name'])] ?? []);
            if ($ids === [] && preg_match('/^(.+),\s*(.+?)\s+[\pL]\.?$/u', $record['name'], $parts)) {
                $key = EClassRecord::normalize($parts[1].', '.$parts[2]);
                $ids = array_keys($withoutMiddleName[$key] ?? []);
            }
            if (count($ids) !== 1) {
                $issues[] = 'Row '.$record['row'].' — '.$record['name'].': '.(count($ids) ? 'name matches more than one learner.' : 'no matching learner in this class.');

                continue;
            }
            $matched[$ids[0]][] = $record;
        }
        $grades = [];
        foreach ($matched as $id => $rows) {
            $record = $rows[0];
            $reason = match (true) {
                count($rows) > 1 => 'appears more than once in the worksheet; all duplicate rows were skipped.',
                ! isset($subjects[$id]) => 'is not enrolled in this subject.',
                in_array($subjects[$id], $locked, true) => 'grade is locked.',
                $record['grade'] === '' => 'Term Grade is blank. Recalculate and save the workbook in Excel if this cell contains a formula.',
                ! is_numeric($record['grade']) => 'Term Grade is not a valid number. Recalculate and save the workbook in Excel.',
                (float) $record['grade'] < 0 || (float) $record['grade'] > 100 => 'Term Grade must be between 0 and 100.',
                default => null,
            };
            if ($reason) {
                $issues[] = 'Row '.$record['row'].' — '.$record['name'].': '.$reason;
            } else {
                $grades[] = ['enrollment_id' => $id, 'grade' => round((float) $record['grade'], 2)];
            }
        }

        return [
            'period' => $period,
            'grades' => $grades,
            'issues' => $issues,
            'unchanged' => $enrollments->count() - count($grades),
        ];
    }
}
