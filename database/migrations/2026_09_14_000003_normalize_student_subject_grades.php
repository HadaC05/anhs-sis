<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // term_ID is the canonical period. Backfill any legacy rows before
        // removing their text period and duplicated semester reference.
        DB::table('student_subject_grades')->orderBy('grade_ID')->each(function (object $grade): void {
            if ($grade->term_ID) {
                return;
            }

            $period = \App\Models\GradingTerm::findSeniorHighPeriodByKey($grade->grading_period);
            $termId = $period['term_ID'] ?? DB::table('grading_terms')->where('key', $grade->grading_period)->value('term_ID');

            if (! $termId) {
                throw new RuntimeException("Grade {$grade->grade_ID} does not have a valid grading term.");
            }

            DB::table('student_subject_grades')->where('grade_ID', $grade->grade_ID)->update(['term_ID' => $termId]);
        });

        $this->dropForeignIfPresent('enrollment_ID');
        $this->dropForeignIfPresent('semester_ID');

        Schema::table('student_subject_grades', function (Blueprint $table): void {
            if (Schema::hasIndex('student_subject_grades', 'student_subject_grades_unique')) {
                $table->dropUnique('student_subject_grades_unique');
            }
            $table->dropColumn(['enrollment_ID', 'semester_ID', 'grading_period']);
            $table->unsignedBigInteger('term_ID')->nullable(false)->change();
            $table->unique(['student_subject_ID', 'assignment_ID', 'term_ID'], 'student_subject_grades_subject_assignment_term_unique');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This data-normalizing migration cannot be safely reversed.');
    }

    private function dropForeignIfPresent(string $column): void
    {
        if (DB::getDriverName() === 'sqlite') {
            try {
                Schema::table('student_subject_grades', fn (Blueprint $table) => $table->dropForeign([$column]));
            } catch (\Throwable) {
                // SQLite rebuilds the table and may already omit this key.
            }

            return;
        }

        $foreignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'student_subject_grades')
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($foreignKey) {
            Schema::table('student_subject_grades', fn (Blueprint $table) => $table->dropForeign($foreignKey));
        }
    }
};
