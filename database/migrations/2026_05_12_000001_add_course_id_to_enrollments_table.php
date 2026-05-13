<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('course_ID')->nullable()->after('cluster_ID');

            $table->foreign('course_ID')
                ->references('course_ID')
                ->on('preferred_courses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['course_ID']);
            $table->dropColumn('course_ID');
        });
    }
};
