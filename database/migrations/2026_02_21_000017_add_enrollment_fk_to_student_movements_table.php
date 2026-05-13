<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('student_movements') && Schema::hasTable('enrollments')) {
            Schema::table('student_movements', function (Blueprint $table) {
                $table->foreign('enrollment_ID')
                    ->references('enrollment_ID')
                    ->on('enrollments')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('student_movements')) {
            Schema::table('student_movements', function (Blueprint $table) {
                $table->dropForeign(['enrollment_ID']);
            });
        }
    }
};
