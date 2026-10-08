<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_reset_runs', function (Blueprint $table): void {
            $table->string('token_hash', 64)->primary();
            $table->timestamp('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_reset_runs');
    }
};
