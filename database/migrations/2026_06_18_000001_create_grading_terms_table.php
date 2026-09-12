<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grading_terms')) {
            Schema::create('grading_terms', function (Blueprint $table) {
                $table->id('term_ID');
                $table->string('key')->unique();
                $table->string('label');
                $table->unsignedSmallInteger('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('grading_term_settings')) {
            Schema::create('grading_term_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('max_terms')->default(4);
                $table->timestamps();
            });
        }

        DB::table('grading_term_settings')->updateOrInsert(
            ['id' => 1],
            ['max_terms' => 4, 'updated_at' => now(), 'created_at' => now()]
        );

        foreach ([1, 2, 3, 4] as $termNumber) {
            DB::table('grading_terms')->updateOrInsert(
                ['key' => 'term_'.$termNumber],
                [
                    'label' => 'Term '.$termNumber,
                    'sort_order' => $termNumber,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->makeGradingPeriodString('student_subject_grades');
        $this->makeGradingPeriodString('student_grades');

        $legacyMap = [
            'q1' => 'term_1',
            'q2' => 'term_2',
            'q3' => 'term_3',
            'q4' => 'term_4',
        ];

        foreach (['student_subject_grades', 'student_grades'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'grading_period')) {
                continue;
            }

            foreach ($legacyMap as $from => $to) {
                DB::table($table)
                    ->where('grading_period', $from)
                    ->update(['grading_period' => $to]);
            }
        }
    }

    public function down(): void
    {
        $legacyMap = [
            'term_1' => 'q1',
            'term_2' => 'q2',
            'term_3' => 'q3',
            'term_4' => 'q4',
        ];

        foreach (['student_subject_grades', 'student_grades'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'grading_period')) {
                continue;
            }

            foreach ($legacyMap as $from => $to) {
                DB::table($table)
                    ->where('grading_period', $from)
                    ->update(['grading_period' => $to]);
            }
        }

        Schema::dropIfExists('grading_term_settings');
        Schema::dropIfExists('grading_terms');
    }

    private function makeGradingPeriodString(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'grading_period')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE {$table} MODIFY grading_period VARCHAR(50) NOT NULL");
        }
    }
};
