<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('enrollment_ID')->unique();
            $table->string('contact_no', 30)->nullable();
            $table->string('birthplace')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('religion')->nullable();
            $table->boolean('ip_community')->default(false);
            $table->string('ip_details')->nullable();
            $table->boolean('four_ps_beneficiary')->default(false);
            $table->string('four_ps_details')->nullable();
            $table->boolean('pwd')->default(false);
            $table->string('pwd_details')->nullable();
            $table->string('last_grade_level_completed')->nullable();
            $table->string('last_school_year_completed')->nullable();
            $table->string('last_school_attended')->nullable();
            $table->string('school_id_from_previous_school')->nullable();
            $table->string('curr_house_no')->nullable();
            $table->string('curr_street_name')->nullable();
            $table->string('curr_barangay')->nullable();
            $table->string('curr_municipality_city')->nullable();
            $table->string('curr_province')->nullable();
            $table->string('curr_country')->nullable();
            $table->string('curr_zip_code')->nullable();
            $table->string('perm_house_no')->nullable();
            $table->string('perm_street_name')->nullable();
            $table->string('perm_barangay')->nullable();
            $table->string('perm_municipality_city')->nullable();
            $table->string('perm_province')->nullable();
            $table->string('perm_country')->nullable();
            $table->string('perm_zip_code')->nullable();
            $table->string('father_lname')->nullable();
            $table->string('father_fname')->nullable();
            $table->string('father_mname')->nullable();
            $table->string('father_suffix')->nullable();
            $table->string('father_contact_no')->nullable();
            $table->string('mother_lname')->nullable();
            $table->string('mother_fname')->nullable();
            $table->string('mother_mname')->nullable();
            $table->string('mother_suffix')->nullable();
            $table->string('mother_contact_no')->nullable();
            $table->string('guardian_lname')->nullable();
            $table->string('guardian_fname')->nullable();
            $table->string('guardian_mname')->nullable();
            $table->string('guardian_suffix')->nullable();
            $table->string('guardian_contact_no')->nullable();
            $table->timestamps();

            $table->foreign('enrollment_ID')
                ->references('enrollment_ID')
                ->on('enrollments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_details');
    }
};
