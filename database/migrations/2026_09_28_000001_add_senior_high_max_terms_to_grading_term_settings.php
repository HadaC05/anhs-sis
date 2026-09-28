<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_term_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('senior_high_max_terms')->default(3);
        });
    }

    public function down(): void
    {
        Schema::table('grading_term_settings', function (Blueprint $table): void {
            $table->dropColumn('senior_high_max_terms');
        });
    }
};
