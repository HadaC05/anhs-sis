<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->increments('track_ID');
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('tracks')->insert([
            ['name' => 'Academic Track', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Technical Professional Track', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
