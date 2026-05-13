<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->increments('doc_ID');
            $table->unsignedBigInteger('student_ID');
            $table->enum('doc_type', ['birth_certificate', 'form_137', 'good_moral', 'id_photo', 'other']);
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('date_uploaded')->nullable();
            $table->timestamp('date_verified')->nullable();
            $table->unsignedInteger('verified_by')->nullable();
            $table->timestamps();

            $table->foreign('student_ID')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();
            $table->foreign('verified_by')
                ->references('staff_id')
                ->on('staffs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
