<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_electives', function (Blueprint $table): void {
            $table->unsignedInteger('enrollment_ID');
            $table->unsignedInteger('subject_ID');
            $table->timestamps();

            $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
            $table->foreign('subject_ID')->references('subject_ID')->on('subjects')->restrictOnDelete();
            $table->primary(['enrollment_ID', 'subject_ID']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_electives');
    }
};
