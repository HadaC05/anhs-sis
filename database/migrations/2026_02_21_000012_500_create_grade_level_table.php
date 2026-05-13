<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_level', function (Blueprint $table) {
            $table->increments('grade_ID');
            $table->string('grade_label')->unique();
            $table->string('category');
            $table->timestamps();
        });

        $grades = [
            ['grade_label' => 'Grade 7', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 8', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 9', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 10', 'category' => 'Junior High School'],
            ['grade_label' => 'Grade 11', 'category' => 'Senior High School'],
            ['grade_label' => 'Grade 12', 'category' => 'Senior High School'],
        ];

        foreach ($grades as $grade) {
            DB::table('grade_level')->updateOrInsert(
                ['grade_label' => $grade['grade_label']],
                [
                    'category' => $grade['category'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $this->convertTable('sections');
        $this->convertTable('enrollments');
    }

    public function down(): void
    {
        $this->restoreTable('enrollments');
        $this->restoreTable('sections');

        Schema::dropIfExists('grade_level');
    }

    private function convertTable(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'grade_ID')) {
            return;
        }

        Schema::table($table, function (Blueprint $schema) {
            $schema->unsignedInteger('grade_ID')->nullable()->after('cluster_ID');
        });

        if (Schema::hasColumn($table, 'grade_level')) {
            DB::statement("
                UPDATE {$table}
                INNER JOIN grade_level ON grade_level.grade_label = CONCAT('Grade ', SUBSTRING({$table}.grade_level, 7))
                SET {$table}.grade_ID = grade_level.grade_ID
            ");

            DB::statement("ALTER TABLE {$table} MODIFY grade_ID INT UNSIGNED NOT NULL");

            Schema::table($table, function (Blueprint $schema) use ($table) {
                $schema->foreign('grade_ID', "{$table}_grade_id_foreign")
                    ->references('grade_ID')
                    ->on('grade_level')
                    ->restrictOnDelete();
                $schema->dropColumn('grade_level');
            });
        }
    }

    private function restoreTable(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'grade_ID')) {
            return;
        }

        Schema::table($table, function (Blueprint $schema) use ($table) {
            $schema->dropForeign("{$table}_grade_id_foreign");
            $schema->enum('grade_level', ['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])
                ->nullable()
                ->after('grade_ID');
        });

        DB::statement("
            UPDATE {$table}
            INNER JOIN grade_level ON grade_level.grade_ID = {$table}.grade_ID
            SET {$table}.grade_level = CONCAT('grade_', SUBSTRING(grade_level.grade_label, 7))
        ");

        DB::statement("ALTER TABLE {$table} MODIFY grade_level ENUM('grade_7','grade_8','grade_9','grade_10','grade_11','grade_12') NOT NULL");

        Schema::table($table, function (Blueprint $schema) {
            $schema->dropColumn('grade_ID');
        });
    }
};
