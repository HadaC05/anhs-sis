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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_inventory');
    }
};
