<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_sf2_uploads', function (Blueprint $table) {
            $table->unsignedSmallInteger('report_year')->nullable();
            $table->string('source_school_year')->nullable();
            $table->boolean('use_section_school_year')->default(false);
            $table->unsignedTinyInteger('school_days')->nullable();
            $table->json('import_rows')->nullable();
            $table->json('class_dates')->nullable();
            $table->timestamp('imported_at')->nullable();
        });

        foreach (['enrollment_monthly_attendance', 'section_attendance_settings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('source_sf2_upload_id')->nullable()->constrained('section_sf2_uploads')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['enrollment_monthly_attendance', 'section_attendance_settings'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('source_sf2_upload_id');
            });
        }

        Schema::table('section_sf2_uploads', function (Blueprint $table) {
            $table->dropColumn(['report_year', 'source_school_year', 'use_section_school_year', 'school_days', 'import_rows', 'class_dates', 'imported_at']);
        });
    }
};
