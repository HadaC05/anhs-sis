<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE enrollments MODIFY enrollment_status ENUM('pending','enrolled','temporarily_enrolled','completed','withdrawn','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE enrollments MODIFY enrollment_status ENUM('pending','enrolled','completed','withdrawn','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
