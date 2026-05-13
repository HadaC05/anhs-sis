<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_subject_grades', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('remarks');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->unsignedInteger('reviewed_by')->nullable()->after('submitted_at');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            $table->index(['status', 'assignment_ID']);
            $table->foreign('reviewed_by')->references('staff_id')->on('staffs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('student_subject_grades', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'assignment_ID']);
            $table->dropColumn(['status', 'submitted_at', 'reviewed_by', 'reviewed_at']);
        });
    }
};
