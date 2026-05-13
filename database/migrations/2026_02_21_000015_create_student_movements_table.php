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
        Schema::create('student_movements', function (Blueprint $table) {
            $table->increments('movement_ID');
            $table->unsignedInteger('enrollment_ID');
            $table->enum('movement_type', [
                'transfer_in',
                'transfer_out',
                'dropout',
                'balik_aral',
                'completion',
            ]);
            $table->unsignedInteger('reason_ID');
            $table->date('effective_date');
            $table->string('previous_school')->nullable();
            $table->string('destination_school')->nullable();
            $table->timestamps();

            $table->foreign('reason_ID')
                ->references('reason_ID')
                ->on('movement_reasons')
                ->restrictOnDelete();
        });

        if (Schema::hasTable('enrollments')) {
            Schema::table('student_movements', function (Blueprint $table) {
                $table->foreign('enrollment_ID')
                    ->references('enrollment_ID')
                    ->on('enrollments')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_movements');
    }
};
