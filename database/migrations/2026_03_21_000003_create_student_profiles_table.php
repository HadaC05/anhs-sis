<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->increments('profile_ID');
            $table->unsignedBigInteger('student_ID');
            $table->boolean('is_4ps')->default(false);
            $table->string('four_ps_household_id')->nullable();
            $table->boolean('is_ip')->default(false);
            $table->string('ip_community')->nullable();
            $table->boolean('has_disability')->default(false);
            $table->string('disability_name')->nullable();
            $table->timestamps();

            $table->foreign('student_ID')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();
            $table->unique('student_ID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
