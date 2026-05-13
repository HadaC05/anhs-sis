<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->increments('guardian_id');
            $table->unsignedBigInteger('student_ID');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('suffix')->nullable();
            $table->enum('relationship', ['father', 'mother', 'guardian']);
            $table->string('contact_no')->nullable();
            $table->timestamps();

            $table->foreign('student_ID')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();
            $table->unique(['student_ID', 'relationship'], 'student_guardian_unique_relationship');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
