<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisory_class_list_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('section_ID');
            $table->unsignedInteger('requested_by');
            $table->string('original_filename');
            $table->longText('file_contents');
            $table->string('status')->default('queued');
            $table->json('result')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('section_ID')->references('section_ID')->on('sections')->cascadeOnDelete();
            $table->foreign('requested_by')->references('staff_id')->on('staffs')->cascadeOnDelete();
            $table->index(['section_ID', 'requested_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advisory_class_list_imports');
    }
};
