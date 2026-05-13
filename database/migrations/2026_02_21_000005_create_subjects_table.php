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
        Schema::create('subjects', function (Blueprint $table) {
            $table->increments('subject_ID');
            $table->unsignedInteger('cluster_ID');
            $table->string('code')->unique();
            $table->string('title');
            $table->enum('type', ['core', 'applied', 'specialized']);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->foreign('cluster_ID')->references('cluster_ID')->on('clusters')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
