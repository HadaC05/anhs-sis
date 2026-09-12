<?php

use App\Models\Month;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('months', function (Blueprint $table) {
            $table->unsignedTinyInteger('month_ID')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        foreach (Month::names() as $monthId => $name) {
            DB::table('months')->insert([
                'month_ID' => $monthId,
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('academic_year_attendance_settings', function (Blueprint $table) {
            $table->foreign('month', 'academic_year_attendance_settings_month_foreign')
                ->references('month_ID')
                ->on('months')
                ->restrictOnDelete();
        });

        Schema::table('enrollment_monthly_attendance', function (Blueprint $table) {
            $table->foreign('month', 'enrollment_monthly_attendance_month_foreign')
                ->references('month_ID')
                ->on('months')
                ->restrictOnDelete();
        });

        Schema::table('section_attendance_settings', function (Blueprint $table) {
            $table->foreign('month', 'section_attendance_settings_month_foreign')
                ->references('month_ID')
                ->on('months')
                ->restrictOnDelete();
        });

        Schema::table('section_sf2_uploads', function (Blueprint $table) {
            $table->foreign('report_month', 'section_sf2_uploads_report_month_foreign')
                ->references('month_ID')
                ->on('months')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('section_sf2_uploads', function (Blueprint $table) {
            $table->dropForeign('section_sf2_uploads_report_month_foreign');
        });

        Schema::table('section_attendance_settings', function (Blueprint $table) {
            $table->dropForeign('section_attendance_settings_month_foreign');
        });

        Schema::table('enrollment_monthly_attendance', function (Blueprint $table) {
            $table->dropForeign('enrollment_monthly_attendance_month_foreign');
        });

        Schema::table('academic_year_attendance_settings', function (Blueprint $table) {
            $table->dropForeign('academic_year_attendance_settings_month_foreign');
        });

        Schema::dropIfExists('months');
    }
};
