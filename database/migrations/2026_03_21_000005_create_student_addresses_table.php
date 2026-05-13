<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_addresses', function (Blueprint $table) {
            $table->increments('address_ID');
            $table->unsignedBigInteger('student_ID');
            $table->enum('address_type', ['current', 'permanent']);
            $table->string('house_no')->nullable();
            $table->string('street_name')->nullable();
            $table->string('barangay')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
            $table->timestamps();

            $table->foreign('student_ID')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();
            $table->unique(['student_ID', 'address_type'], 'student_addresses_unique_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_addresses');
    }
};
