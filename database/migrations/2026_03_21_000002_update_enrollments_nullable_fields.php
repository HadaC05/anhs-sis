<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('section_ID')->nullable()->change();
            $table->unsignedInteger('cluster_ID')->nullable()->change();
            // PostgreSQL cannot use its inline CHECK-based enum definition
            // while altering a column. The existing constraint remains in
            // place; only the nullability needs to change.
            if (DB::getDriverName() === 'pgsql') {
                $table->string('semester')->nullable()->change();
            } else {
                $table->enum('semester', ['first', 'second'])->nullable()->change();
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_ID', 'SY_ID'], 'enrollments_unique_student_sy');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollments_unique_student_sy');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('section_ID')->nullable(false)->change();
            $table->unsignedInteger('cluster_ID')->nullable(false)->change();
            if (DB::getDriverName() === 'pgsql') {
                $table->string('semester')->nullable(false)->change();
            } else {
                $table->enum('semester', ['first', 'second'])->nullable(false)->change();
            }
        });
    }
};
