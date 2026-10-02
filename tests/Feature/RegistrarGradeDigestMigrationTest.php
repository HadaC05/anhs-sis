<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('grade digest migration resumes after partial table creation', function () {
    $migration = require database_path('migrations/2026_09_30_000001_create_registrar_grade_digest_tables.php');
    $migration->down();

    Schema::create('pending_grade_submissions', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('assignment_ID');
        $table->unsignedInteger('term_ID');
        $table->timestamp('submitted_at');
        $table->unique(['assignment_ID', 'term_ID']);
        $table->foreign('assignment_ID')->references('assignment_ID')->on('teacher_subject_assignments')->cascadeOnDelete();
    });

    $migration->up();
    $foreignKeys = collect(Schema::getForeignKeys('pending_grade_submissions'));
    expect($foreignKeys->contains(fn (array $key): bool => $key['columns'] === ['term_ID'] && $key['foreign_table'] === 'grading_terms'))->toBeTrue();

    DB::table('registrar_grade_digest_state')->where('id', 1)->update(['last_sent_at' => '2026-09-30 12:00:00']);
    $migration->up();
    expect(DB::table('registrar_grade_digest_state')->count())->toBe(1)
        ->and(DB::table('registrar_grade_digest_state')->value('last_sent_at'))->toBe('2026-09-30 12:00:00');
});
