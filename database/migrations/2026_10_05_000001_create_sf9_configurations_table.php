<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sf9_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('junior_high')->default('jhs_legacy');
            $table->string('senior_high')->default('shs_current');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sf9_configurations');
    }
};
