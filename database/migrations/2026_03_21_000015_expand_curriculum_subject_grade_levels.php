<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE curriculum_subjects MODIFY grade_level ENUM('grade_7','grade_8','grade_9','grade_10','grade_11','grade_12') NOT NULL"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            "ALTER TABLE curriculum_subjects MODIFY grade_level ENUM('grade_11','grade_12') NOT NULL"
        );
    }
};
