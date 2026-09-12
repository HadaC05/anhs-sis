<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'last_grade_level_completed')) {
                $table->string('last_grade_level_completed')->nullable()->after('learner_type');
            }

            if (! Schema::hasColumn('enrollments', 'last_school_year_completed')) {
                $table->string('last_school_year_completed')->nullable()->after('last_grade_level_completed');
            }

            if (! Schema::hasColumn('enrollments', 'last_school_attended')) {
                $table->string('last_school_attended')->nullable()->after('last_school_year_completed');
            }

            if (! Schema::hasColumn('enrollments', 'school_id_from_previous_school')) {
                $table->string('school_id_from_previous_school')->nullable()->after('last_school_attended');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $columns = [
                'last_grade_level_completed',
                'last_school_year_completed',
                'last_school_attended',
                'school_id_from_previous_school',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
