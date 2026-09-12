<?php

use App\Models\GradeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createGradeStatusesTable();
        $this->addPeriodAndStatusColumns();
        $this->remapSeniorHighPeriodKeys();
        $this->backfillGradeReferences();
        $this->replaceStatusColumn();
        $this->addForeignKeys();
    }

    public function down(): void
    {
        if (Schema::hasTable('student_subject_grades')) {
            $this->dropGradeForeignKeys();

            if (! Schema::hasColumn('student_subject_grades', 'status')) {
                Schema::table('student_subject_grades', function (Blueprint $table): void {
                    $table->string('status', 20)->default('draft')->after('remarks');
                });
            }

            $statusSlugs = DB::table('grade_statuses')->pluck('slug', 'grade_status_ID');

            foreach ($statusSlugs as $id => $slug) {
                DB::table('student_subject_grades')
                    ->where('grade_status_ID', $id)
                    ->update(['status' => $slug]);
            }

            Schema::table('student_subject_grades', function (Blueprint $table): void {
                $columns = array_values(array_filter([
                    Schema::hasColumn('student_subject_grades', 'grade_status_ID') ? 'grade_status_ID' : null,
                    Schema::hasColumn('student_subject_grades', 'term_ID') ? 'term_ID' : null,
                    Schema::hasColumn('student_subject_grades', 'quarter_ID') ? 'quarter_ID' : null,
                    Schema::hasColumn('student_subject_grades', 'semester_ID') ? 'semester_ID' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('grade_statuses');
        GradeStatus::clearOptionsCache();
    }

    private function createGradeStatusesTable(): void
    {
        if (! Schema::hasTable('grade_statuses')) {
            Schema::create('grade_statuses', function (Blueprint $table): void {
                $table->increments('grade_status_ID');
                $table->string('slug')->unique();
                $table->string('name')->unique();
                $table->unsignedTinyInteger('sort_order');
                $table->timestamps();
            });
        }

        $now = now();

        foreach (GradeStatus::definitions() as $status) {
            DB::table('grade_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        GradeStatus::clearOptionsCache();
    }

    private function addPeriodAndStatusColumns(): void
    {
        if (! Schema::hasTable('student_subject_grades')) {
            return;
        }

        Schema::table('student_subject_grades', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_subject_grades', 'grade_status_ID')) {
                $table->unsignedInteger('grade_status_ID')->nullable()->after('remarks');
            }

            if (! Schema::hasColumn('student_subject_grades', 'term_ID')) {
                $table->unsignedBigInteger('term_ID')->nullable()->after('assignment_ID');
            }

            if (! Schema::hasColumn('student_subject_grades', 'semester_ID')) {
                $table->unsignedInteger('semester_ID')->nullable()->after('term_ID');
            }

            if (! Schema::hasColumn('student_subject_grades', 'quarter_ID')) {
                $table->unsignedInteger('quarter_ID')->nullable()->after('semester_ID');
            }
        });

        $this->alignTermIdColumnType();
    }

    private function alignTermIdColumnType(): void
    {
        if (! Schema::hasTable('student_subject_grades') || ! Schema::hasColumn('student_subject_grades', 'term_ID')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $type = strtolower((string) DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'student_subject_grades')
            ->where('COLUMN_NAME', 'term_ID')
            ->value('COLUMN_TYPE'));

        if ($type !== '' && ! str_contains($type, 'bigint')) {
            DB::statement('ALTER TABLE student_subject_grades MODIFY term_ID BIGINT UNSIGNED NULL');
        }
    }

    private function remapSeniorHighPeriodKeys(): void
    {
        if (! Schema::hasTable('student_subject_grades')) {
            return;
        }

        $legacyMap = [
            'q1' => 'shs_sem1_q1',
            'term_1' => 'shs_sem1_q1',
            'q2' => 'shs_sem1_q2',
            'term_2' => 'shs_sem1_q2',
            'q3' => 'shs_sem2_q1',
            'term_3' => 'shs_sem2_q1',
            'q4' => 'shs_sem2_q2',
            'term_4' => 'shs_sem2_q2',
        ];

        $seniorHighEnrollmentIds = $this->seniorHighEnrollmentIds();

        if ($seniorHighEnrollmentIds !== []) {
            foreach ($legacyMap as $from => $to) {
                DB::table('student_subject_grades')
                    ->whereIn('enrollment_ID', $seniorHighEnrollmentIds)
                    ->where('grading_period', $from)
                    ->update(['grading_period' => $to]);
            }
        }

        if (! Schema::hasTable('assignment_grade_term_unlocks')) {
            return;
        }

        $seniorHighAssignmentIds = $this->seniorHighAssignmentIds();

        if ($seniorHighAssignmentIds === []) {
            return;
        }

        foreach ($legacyMap as $from => $to) {
            DB::table('assignment_grade_term_unlocks')
                ->whereIn('assignment_ID', $seniorHighAssignmentIds)
                ->where('grading_period', $from)
                ->update(['grading_period' => $to]);
        }
    }

    private function backfillGradeReferences(): void
    {
        if (! Schema::hasTable('student_subject_grades')) {
            return;
        }

        $statusIds = DB::table('grade_statuses')->pluck('grade_status_ID', 'slug');
        $draftId = $statusIds[GradeStatus::DRAFT] ?? null;

        if (Schema::hasColumn('student_subject_grades', 'status')) {
            foreach ($statusIds as $slug => $id) {
                DB::table('student_subject_grades')
                    ->where('status', $slug)
                    ->update(['grade_status_ID' => $id]);
            }
        }

        if ($draftId !== null) {
            DB::table('student_subject_grades')
                ->whereNull('grade_status_ID')
                ->update(['grade_status_ID' => $draftId]);
        }

        $termIds = Schema::hasTable('grading_terms')
            ? DB::table('grading_terms')->pluck('term_ID', 'key')
            : collect();
        $quarters = Schema::hasTable('grading_quarters')
            ? DB::table('grading_quarters')->get(['quarter_ID', 'semester_ID', 'key'])->keyBy('key')
            : collect();

        foreach ($termIds as $key => $termId) {
            DB::table('student_subject_grades')
                ->where('grading_period', $key)
                ->update([
                    'term_ID' => $termId,
                    'semester_ID' => null,
                    'quarter_ID' => null,
                ]);
        }

        foreach ($quarters as $key => $quarter) {
            DB::table('student_subject_grades')
                ->where('grading_period', $key)
                ->update([
                    'term_ID' => null,
                    'semester_ID' => $quarter->semester_ID,
                    'quarter_ID' => $quarter->quarter_ID,
                ]);
        }
    }

    private function replaceStatusColumn(): void
    {
        if (! Schema::hasTable('student_subject_grades')) {
            return;
        }

        if (Schema::hasColumn('student_subject_grades', 'status')) {
            try {
                Schema::table('student_subject_grades', function (Blueprint $table): void {
                    $table->dropIndex(['status', 'assignment_ID']);
                });
            } catch (\Throwable) {
                // The composite status index may already be absent on some drivers.
            }

            Schema::table('student_subject_grades', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }

        if (! Schema::hasIndex('student_subject_grades', 'student_subject_grades_status_assignment_index')) {
            Schema::table('student_subject_grades', function (Blueprint $table): void {
                $table->index(['grade_status_ID', 'assignment_ID'], 'student_subject_grades_status_assignment_index');
            });
        }
    }

    private function addForeignKeys(): void
    {
        if (! Schema::hasTable('student_subject_grades')) {
            return;
        }

        $this->addForeignKeyIfMissing(
            'student_subject_grades_status_foreign',
            'grade_status_ID',
            'grade_statuses',
            'grade_status_ID',
        );

        if (Schema::hasTable('grading_terms')) {
            $this->addForeignKeyIfMissing(
                'student_subject_grades_term_foreign',
                'term_ID',
                'grading_terms',
                'term_ID',
            );
        }

        if (Schema::hasTable('grading_semesters')) {
            $this->addForeignKeyIfMissing(
                'student_subject_grades_semester_foreign',
                'semester_ID',
                'grading_semesters',
                'semester_ID',
            );
        }

        if (Schema::hasTable('grading_quarters')) {
            $this->addForeignKeyIfMissing(
                'student_subject_grades_quarter_foreign',
                'quarter_ID',
                'grading_quarters',
                'quarter_ID',
            );
        }
    }

    private function addForeignKeyIfMissing(string $name, string $column, string $onTable, string $references): void
    {
        if (! Schema::hasColumn('student_subject_grades', $column)) {
            return;
        }

        try {
            Schema::table('student_subject_grades', function (Blueprint $table) use ($name, $column, $onTable, $references): void {
                $table->foreign($column, $name)
                    ->references($references)
                    ->on($onTable)
                    ->restrictOnDelete();
            });
        } catch (\Throwable) {
            // The foreign key may already exist after a partial migrate.
        }
    }

    private function dropGradeForeignKeys(): void
    {
        Schema::table('student_subject_grades', function (Blueprint $table): void {
            foreach ([
                'student_subject_grades_status_foreign',
                'student_subject_grades_term_foreign',
                'student_subject_grades_semester_foreign',
                'student_subject_grades_quarter_foreign',
            ] as $foreign) {
                try {
                    $table->dropForeign($foreign);
                } catch (\Throwable) {
                    // The foreign key may not exist on every driver during rollback.
                }
            }

            if (Schema::hasIndex('student_subject_grades', 'student_subject_grades_status_assignment_index')) {
                $table->dropIndex('student_subject_grades_status_assignment_index');
            }
        });
    }

    /**
     * @return list<int>
     */
    private function seniorHighEnrollmentIds(): array
    {
        if (! Schema::hasTable('enrollments') || ! Schema::hasTable('grade_level')) {
            return [];
        }

        return DB::table('enrollments')
            ->join('grade_level', 'enrollments.grade_ID', '=', 'grade_level.grade_ID')
            ->where('grade_level.category', 'Senior High School')
            ->pluck('enrollments.enrollment_ID')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function seniorHighAssignmentIds(): array
    {
        if (! Schema::hasTable('teacher_subject_assignments') || ! Schema::hasTable('sections') || ! Schema::hasTable('grade_level')) {
            return [];
        }

        return DB::table('teacher_subject_assignments')
            ->join('sections', 'teacher_subject_assignments.section_ID', '=', 'sections.section_ID')
            ->join('grade_level', 'sections.grade_ID', '=', 'grade_level.grade_ID')
            ->where('grade_level.category', 'Senior High School')
            ->pluck('teacher_subject_assignments.assignment_ID')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
};
