<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('converts existing learner and teacher links without changing their ids', function () {
    Schema::create('subjects', function (Blueprint $table): void {
        $table->increments('subject_ID');
    });
    Schema::create('curriculum_subjects', function (Blueprint $table): void {
        $table->increments('curr_subj_ID');
        $table->unsignedInteger('subject_ID');
        $table->foreign('subject_ID')->references('subject_ID')->on('subjects');
    });
    Schema::create('student_subjects', function (Blueprint $table): void {
        $table->increments('student_subject_ID');
        $table->unsignedInteger('enrollment_ID');
        $table->unsignedInteger('curr_subj_ID');
        $table->foreign('curr_subj_ID')->references('curr_subj_ID')->on('curriculum_subjects')->restrictOnDelete();
        $table->unique(['enrollment_ID', 'curr_subj_ID'], 'student_subjects_enrollment_curriculum_subject_unique');
    });
    Schema::create('teacher_subject_assignments', function (Blueprint $table): void {
        $table->increments('assignment_ID');
        $table->unsignedInteger('section_ID');
        $table->unsignedInteger('curr_subj_ID');
        $table->foreign('curr_subj_ID')->references('curr_subj_ID')->on('curriculum_subjects')->cascadeOnDelete();
        $table->unique(['section_ID', 'curr_subj_ID'], 'teacher_subject_assignments_unique');
    });

    DB::table('subjects')->insert(['subject_ID' => 47]);
    DB::table('curriculum_subjects')->insert(['curr_subj_ID' => 73, 'subject_ID' => 47]);
    DB::table('student_subjects')->insert(['student_subject_ID' => 11, 'enrollment_ID' => 5, 'curr_subj_ID' => 73]);
    DB::table('teacher_subject_assignments')->insert(['assignment_ID' => 19, 'section_ID' => 8, 'curr_subj_ID' => 73]);

    $migration = require database_path('migrations/2026_10_08_000006_500_convert_legacy_subject_references.php');
    $migration->up();

    expect(DB::table('student_subjects')->where('student_subject_ID', 11)->value('subject_ID'))->toBe(47)
        ->and(DB::table('teacher_subject_assignments')->where('assignment_ID', 19)->value('subject_ID'))->toBe(47)
        ->and(Schema::hasColumn('student_subjects', 'curr_subj_ID'))->toBeFalse()
        ->and(Schema::hasColumn('teacher_subject_assignments', 'curr_subj_ID'))->toBeFalse();
});
