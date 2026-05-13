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
        Schema::create('staffs', function (Blueprint $table) {
            $table->increments('staff_id');
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('username')->unique();
            $table->string('password');
            $table->boolean('change_password')->default(true);
            $table->timestamp('password_changed_at')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('mobile_no', 20)->nullable();
            $table->string('employee_no')->nullable()->unique();
            $table->string('plantilla_item_no')->nullable()->unique();
            $table->enum('appointment_status', ['permanent', 'probationary', 'contractual', 'job_order', 'part_time'])->nullable();
            $table->enum('fund_source', ['government', 'private', 'grant', 'other'])->nullable();
            $table->string('degree_earned')->nullable();
            $table->string('major_specialization')->nullable();
            $table->unsignedInteger('teaching_minutes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};
