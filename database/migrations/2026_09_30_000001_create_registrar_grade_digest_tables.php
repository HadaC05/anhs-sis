<?php

use App\Models\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_grade_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('assignment_ID');
            $table->unsignedInteger('term_ID');
            $table->timestamp('submitted_at');
            $table->unique(['assignment_ID', 'term_ID']);
            $table->foreign('assignment_ID')->references('assignment_ID')->on('teacher_subject_assignments')->cascadeOnDelete();
            $table->foreign('term_ID')->references('term_ID')->on('grading_terms')->cascadeOnDelete();
        });
        Schema::create('registrar_grade_digest_state', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->timestamp('last_sent_at');
        });
        DB::table('registrar_grade_digest_state')->insert(['id' => 1, 'last_sent_at' => now()]);
        DB::table('notification_types')->updateOrInsert(
            ['slug' => NotificationType::GRADE_SUBMISSIONS_DIGEST],
            ['name' => 'New grade submissions', 'sort_order' => 9, 'created_at' => now(), 'updated_at' => now()],
        );
        NotificationType::clearOptionsCache();
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_grade_submissions');
        Schema::dropIfExists('registrar_grade_digest_state');
        // Retain the notification type because delivered notifications may reference it.
    }
};
