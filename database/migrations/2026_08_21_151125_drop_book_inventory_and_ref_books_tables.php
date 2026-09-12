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
        Schema::dropIfExists('book_inventory');
        Schema::dropIfExists('ref_books');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('ref_books', function (Blueprint $table) {
            $table->increments('book_ID');
            $table->unsignedInteger('subject_ID');
            $table->string('book_code')->unique();
            $table->string('title');
            $table->string('author');
            $table->enum('grade_level', [
                'grade_7',
                'grade_8',
                'grade_9',
                'grade_10',
                'grade_11',
                'grade_12',
            ]);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->foreign('subject_ID')->references('subject_ID')->on('subjects')->cascadeOnDelete();
        });

        Schema::create('book_inventory', function (Blueprint $table) {
            $table->increments('inventory_ID');
            $table->unsignedInteger('book_ID');
            $table->string('property_no')->unique();
            $table->enum('condition', ['good', 'fair', 'damaged']);
            $table->enum('status', ['available', 'issued', 'lost']);
            $table->enum('record_status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->foreign('book_ID')->references('book_ID')->on('ref_books')->cascadeOnDelete();
        });
    }
};
