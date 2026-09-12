<?php

use App\Models\GradingSemester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSettingsTermColumn();
        $this->remapPeriodKeys();
        $this->backfillSeniorHighTermReferences();
        $this->dropQuarterForeignKeys();
        $this->dropQuarterColumns();
        $this->addSettingsTermForeignKey();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('grading_quarters');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $this->restoreQuarterTable();
        $this->restoreQuarterColumns();
        $this->remapPeriodKeysToQuarters();
        $this->dropSettingsTermColumn();
    }

    private function addSettingsTermColumn(): void
    {
        if (! Schema::hasTable('grading_term_settings') || Schema::hasColumn('grading_term_settings', 'term_ID')) {
            return;
        }

        Schema::table('grading_term_settings', function (Blueprint $table): void {
            $table->unsignedBigInteger('term_ID')->nullable()->after('semester_ID');
        });
    }

    private function remapPeriodKeys(): void
    {
        $legacyMap = [
            'shs_sem1_q1' => 'shs_sem1_term_1',
            'shs_sem1_q2' => 'shs_sem1_term_2',
            'shs_sem2_q1' => 'shs_sem2_term_1',
            'shs_sem2_q2' => 'shs_sem2_term_2',
        ];

        foreach (['student_subject_grades', 'student_observed_values', 'assignment_grade_term_unlocks'] as $table) {
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

    private function backfillSeniorHighTermReferences(): void
    {
        $termIds = Schema::hasTable('grading_terms')
            ? DB::table('grading_terms')->orderBy('sort_order')->orderBy('term_ID')->pluck('term_ID', 'key')
            : collect();
        $semesterIds = Schema::hasTable('grading_semesters')
            ? DB::table('grading_semesters')->pluck('semester_ID', 'key')
            : collect();

        if (Schema::hasTable('student_subject_grades') && Schema::hasColumn('student_subject_grades', 'term_ID')) {
            foreach ($this->seniorHighPeriodMap() as $periodKey => $meta) {
                $termId = $termIds[$meta['term_key']] ?? null;
                $semesterId = $semesterIds[$meta['semester']] ?? null;

                if ($termId === null || $semesterId === null) {
                    continue;
                }

                DB::table('student_subject_grades')
                    ->where('grading_period', $periodKey)
                    ->update([
                        'term_ID' => $termId,
                        'semester_ID' => $semesterId,
                    ]);
            }

            if (Schema::hasColumn('student_subject_grades', 'quarter_ID')) {
                DB::table('student_subject_grades')->update(['quarter_ID' => null]);
            }
        }

        if (! Schema::hasTable('grading_term_settings') || ! Schema::hasColumn('grading_term_settings', 'term_ID')) {
            return;
        }

        $defaultTermId = $termIds['term_1'] ?? $termIds->first();
        $defaultSemesterId = $semesterIds[GradingSemester::FIRST] ?? $semesterIds->first();

        $rows = DB::table('grading_term_settings')->get();

        foreach ($rows as $row) {
            $termNumber = 1;

            if (Schema::hasColumn('grading_term_settings', 'quarter_ID') && $row->quarter_ID) {
                $quarterNumber = (int) DB::table('grading_quarters')
                    ->where('quarter_ID', $row->quarter_ID)
                    ->value('quarter_number');
                $termNumber = $quarterNumber === 2 ? 2 : 1;
            }

            $termKey = 'term_'.$termNumber;
            $termId = $termIds[$termKey] ?? $defaultTermId;
            $semesterId = $row->semester_ID ?? $defaultSemesterId;

            DB::table('grading_term_settings')
                ->where('id', $row->id)
                ->update([
                    'semester_ID' => $semesterId,
                    'term_ID' => $termId,
                ]);
        }
    }

    private function dropQuarterForeignKeys(): void
    {
        $this->dropForeignKey('grading_term_settings', 'quarter_ID', 'grading_term_settings_quarter_foreign');
        $this->dropForeignKey('student_subject_grades', 'quarter_ID', 'student_subject_grades_quarter_foreign');
    }

    private function dropForeignKey(string $table, string $column, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $name): void {
                if (DB::getDriverName() === 'sqlite') {
                    $blueprint->dropForeign([$column]);

                    return;
                }

                $blueprint->dropForeign($name);
            });
        } catch (Throwable) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                    $blueprint->dropForeign([$column]);
                });
            } catch (Throwable) {
                // The foreign key may already be absent.
            }
        }
    }

    private function dropQuarterColumns(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('grading_term_settings') && Schema::hasColumn('grading_term_settings', 'quarter_ID')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->dropColumn('quarter_ID');
            });
        }

        if (Schema::hasTable('student_subject_grades') && Schema::hasColumn('student_subject_grades', 'quarter_ID')) {
            Schema::table('student_subject_grades', function (Blueprint $table): void {
                $table->dropColumn('quarter_ID');
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    private function addSettingsTermForeignKey(): void
    {
        if (
            ! Schema::hasTable('grading_term_settings')
            || ! Schema::hasColumn('grading_term_settings', 'term_ID')
            || ! Schema::hasTable('grading_terms')
        ) {
            return;
        }

        try {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->foreign('term_ID', 'grading_term_settings_term_foreign')
                    ->references('term_ID')
                    ->on('grading_terms')
                    ->restrictOnDelete();
            });
        } catch (Throwable) {
            // The foreign key may already exist after a partial migrate.
        }
    }

    private function restoreQuarterTable(): void
    {
        if (Schema::hasTable('grading_quarters') || ! Schema::hasTable('grading_semesters')) {
            return;
        }

        Schema::create('grading_quarters', function (Blueprint $table): void {
            $table->increments('quarter_ID');
            $table->unsignedInteger('semester_ID');
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedTinyInteger('quarter_number');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->unsignedInteger('grading_period_status_ID');
            $table->timestamps();

            $table->unique(['semester_ID', 'quarter_number'], 'grading_quarters_semester_quarter_unique');
            $table->foreign('semester_ID', 'grading_quarters_semester_foreign')
                ->references('semester_ID')
                ->on('grading_semesters')
                ->restrictOnDelete();
            $table->foreign('grading_period_status_ID', 'grading_quarters_status_foreign')
                ->references('grading_period_status_ID')
                ->on('grading_period_statuses')
                ->restrictOnDelete();
        });

        $now = now();
        $activeId = DB::table('grading_period_statuses')->where('slug', 'active')->value('grading_period_status_ID');
        $semesterIds = DB::table('grading_semesters')->pluck('semester_ID', 'key');

        $quarters = [
            ['semester_key' => GradingSemester::FIRST, 'key' => 'shs_sem1_q1', 'label' => 'Quarter 1', 'quarter_number' => 1, 'sort_order' => 1],
            ['semester_key' => GradingSemester::FIRST, 'key' => 'shs_sem1_q2', 'label' => 'Quarter 2', 'quarter_number' => 2, 'sort_order' => 2],
            ['semester_key' => GradingSemester::SECOND, 'key' => 'shs_sem2_q1', 'label' => 'Quarter 1', 'quarter_number' => 1, 'sort_order' => 1],
            ['semester_key' => GradingSemester::SECOND, 'key' => 'shs_sem2_q2', 'label' => 'Quarter 2', 'quarter_number' => 2, 'sort_order' => 2],
        ];

        foreach ($quarters as $quarter) {
            $semesterId = $semesterIds[$quarter['semester_key']] ?? null;

            if ($semesterId === null || $activeId === null) {
                continue;
            }

            DB::table('grading_quarters')->insert([
                'semester_ID' => $semesterId,
                'key' => $quarter['key'],
                'label' => $quarter['label'],
                'quarter_number' => $quarter['quarter_number'],
                'sort_order' => $quarter['sort_order'],
                'grading_period_status_ID' => $activeId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function restoreQuarterColumns(): void
    {
        if (Schema::hasTable('grading_term_settings') && ! Schema::hasColumn('grading_term_settings', 'quarter_ID')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->unsignedInteger('quarter_ID')->nullable()->after('semester_ID');
            });

            $rows = DB::table('grading_term_settings')->get();
            $termKeys = Schema::hasTable('grading_terms')
                ? DB::table('grading_terms')->pluck('key', 'term_ID')
                : collect();

            foreach ($rows as $row) {
                $termKey = $termKeys[$row->term_ID ?? 0] ?? 'term_1';
                $termNumber = str_ends_with((string) $termKey, '_2') ? 2 : 1;
                $semesterKey = DB::table('grading_semesters')->where('semester_ID', $row->semester_ID)->value('key') ?: GradingSemester::FIRST;
                $quarterKey = $semesterKey === GradingSemester::SECOND
                    ? ($termNumber === 2 ? 'shs_sem2_q2' : 'shs_sem2_q1')
                    : ($termNumber === 2 ? 'shs_sem1_q2' : 'shs_sem1_q1');
                $quarterId = DB::table('grading_quarters')->where('key', $quarterKey)->value('quarter_ID');

                DB::table('grading_term_settings')
                    ->where('id', $row->id)
                    ->update(['quarter_ID' => $quarterId]);
            }

            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->foreign('quarter_ID', 'grading_term_settings_quarter_foreign')
                    ->references('quarter_ID')
                    ->on('grading_quarters')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('student_subject_grades') && ! Schema::hasColumn('student_subject_grades', 'quarter_ID')) {
            Schema::table('student_subject_grades', function (Blueprint $table): void {
                $table->unsignedInteger('quarter_ID')->nullable()->after('semester_ID');
            });

            foreach ($this->seniorHighPeriodMap() as $periodKey => $meta) {
                $quarterKey = $meta['legacy_quarter_key'] ?? null;

                if (! $quarterKey) {
                    continue;
                }

                $quarter = DB::table('grading_quarters')->where('key', $quarterKey)->first();

                if ($quarter === null) {
                    continue;
                }

                DB::table('student_subject_grades')
                    ->where('grading_period', $periodKey)
                    ->update([
                        'quarter_ID' => $quarter->quarter_ID,
                        'term_ID' => null,
                    ]);
            }

            Schema::table('student_subject_grades', function (Blueprint $table): void {
                $table->foreign('quarter_ID', 'student_subject_grades_quarter_foreign')
                    ->references('quarter_ID')
                    ->on('grading_quarters')
                    ->restrictOnDelete();
            });
        }
    }

    private function remapPeriodKeysToQuarters(): void
    {
        $legacyMap = [
            'shs_sem1_term_1' => 'shs_sem1_q1',
            'shs_sem1_term_2' => 'shs_sem1_q2',
            'shs_sem2_term_1' => 'shs_sem2_q1',
            'shs_sem2_term_2' => 'shs_sem2_q2',
        ];

        foreach (['student_subject_grades', 'student_observed_values', 'assignment_grade_term_unlocks'] as $table) {
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

    private function dropSettingsTermColumn(): void
    {
        if (! Schema::hasTable('grading_term_settings') || ! Schema::hasColumn('grading_term_settings', 'term_ID')) {
            return;
        }

        try {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->dropForeign('grading_term_settings_term_foreign');
            });
        } catch (Throwable) {
            // The foreign key may already be absent.
        }

        Schema::table('grading_term_settings', function (Blueprint $table): void {
            $table->dropColumn('term_ID');
        });
    }

    /**
     * @return array<string, array{semester: string, term_key: string, legacy_quarter_key: string|null}>
     */
    private function seniorHighPeriodMap(): array
    {
        return [
            'shs_sem1_term_1' => ['semester' => GradingSemester::FIRST, 'term_key' => 'term_1', 'legacy_quarter_key' => 'shs_sem1_q1'],
            'shs_sem1_term_2' => ['semester' => GradingSemester::FIRST, 'term_key' => 'term_2', 'legacy_quarter_key' => 'shs_sem1_q2'],
            'shs_sem1_term_3' => ['semester' => GradingSemester::FIRST, 'term_key' => 'term_3', 'legacy_quarter_key' => null],
            'shs_sem2_term_1' => ['semester' => GradingSemester::SECOND, 'term_key' => 'term_1', 'legacy_quarter_key' => 'shs_sem2_q1'],
            'shs_sem2_term_2' => ['semester' => GradingSemester::SECOND, 'term_key' => 'term_2', 'legacy_quarter_key' => 'shs_sem2_q2'],
            'shs_sem2_term_3' => ['semester' => GradingSemester::SECOND, 'term_key' => 'term_3', 'legacy_quarter_key' => null],
        ];
    }
};
