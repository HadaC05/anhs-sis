<?php

use App\Models\EnrollmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('enrollments') || Schema::hasColumn('enrollments', 'enrollment_status_ID')) {
            return;
        }

        if (! Schema::hasColumn('enrollments', 'enrollment_status')) {
            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign('enrollments_enrollment_status_foreign');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('enrollment_status_ID')->nullable()->after('grade_ID');
        });

        $statusIds = DB::table('enrollment_statuses')->pluck('enrollment_status_ID', 'slug');
        $enrolledId = $statusIds[EnrollmentStatus::ENROLLED] ?? null;
        $pendingId = $statusIds[EnrollmentStatus::PENDING] ?? null;

        foreach ($statusIds as $slug => $id) {
            DB::table('enrollments')->where('enrollment_status', $slug)->update([
                'enrollment_status_ID' => $id,
            ]);
        }

        DB::table('enrollments')->where('enrollment_status', 'completed')->update([
            'enrollment_status_ID' => $enrolledId,
        ]);

        DB::table('enrollments')->whereNull('enrollment_status_ID')->update([
            'enrollment_status_ID' => $pendingId,
        ]);

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('enrollment_status');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE enrollments MODIFY enrollment_status_ID INT UNSIGNED NOT NULL');
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('enrollment_status_ID', 'enrollments_enrollment_status_id_foreign')
                ->references('enrollment_status_ID')
                ->on('enrollment_statuses')
                ->restrictOnDelete();
        });

        EnrollmentStatus::clearOptionsCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('enrollments') || ! Schema::hasColumn('enrollments', 'enrollment_status_ID')) {
            return;
        }

        if (Schema::hasColumn('enrollments', 'enrollment_status')) {
            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign('enrollments_enrollment_status_id_foreign');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('enrollment_status')->default('pending')->after('enrollment_status_ID');
        });

        $statusSlugs = DB::table('enrollment_statuses')->pluck('slug', 'enrollment_status_ID');

        foreach ($statusSlugs as $id => $slug) {
            DB::table('enrollments')
                ->where('enrollment_status_ID', $id)
                ->update(['enrollment_status' => $slug]);
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('enrollment_status_ID');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('enrollment_status', 'enrollments_enrollment_status_foreign')
                ->references('slug')
                ->on('enrollment_statuses')
                ->restrictOnDelete();
        });

        EnrollmentStatus::clearOptionsCache();
    }
};
