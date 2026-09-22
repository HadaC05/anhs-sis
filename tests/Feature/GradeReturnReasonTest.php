<?php

use App\Models\GradeReturnReason;
use Illuminate\Support\Facades\Schema;

test('grade return reasons are seeded for registrar review', function () {
    expect(Schema::hasTable('grade_return_reasons'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_grades', 'grade_return_reason_ID'))->toBeTrue()
        ->and(GradeReturnReason::query()->orderBy('name')->pluck('name')->all())
        ->toContain(
            'Incomplete or missing grades',
            'Incorrect grade entry',
            'Incorrect remarks',
        );
});
