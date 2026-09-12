<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_observed_values')) {
            return;
        }

        Schema::table('student_observed_values', function (Blueprint $table) {
            if (! Schema::hasColumn('student_observed_values', 'status')) {
                $table->string('status', 20)->default('draft')->after('marking');
            }

            if (! Schema::hasColumn('student_observed_values', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('student_observed_values', 'reviewed_by')) {
                $table->unsignedInteger('reviewed_by')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn('student_observed_values', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        foreach (['q1' => 'term_1', 'q2' => 'term_2', 'q3' => 'term_3', 'q4' => 'term_4'] as $from => $to) {
            DB::table('student_observed_values')
                ->where('grading_period', $from)
                ->update(['grading_period' => $to]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_observed_values')) {
            return;
        }

        Schema::table('student_observed_values', function (Blueprint $table) {
            $table->dropColumn(['status', 'submitted_at', 'reviewed_by', 'reviewed_at']);
        });
    }
};
