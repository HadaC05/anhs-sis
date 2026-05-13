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
        Schema::create('preferred_courses', function (Blueprint $table) {
            $table->increments('course_ID');
            $table->unsignedInteger('cluster_ID');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('cluster_ID')->references('cluster_ID')->on('clusters')->cascadeOnDelete();
            $table->unique(['cluster_ID', 'name'], 'preferred_courses_cluster_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferred_courses');
    }
};
