<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_sf2_uploads', function (Blueprint $table) {
            $table->json('sf2_layout')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('section_sf2_uploads', function (Blueprint $table) {
            $table->dropColumn('sf2_layout');
        });
    }
};
