<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Populate the lookup on deployment, including rooms from archived sections.
        // Keep the original names so existing section assignments still match.
        DB::table('sections')->select('room')->whereNotNull('room')->distinct()
            ->orderBy('room')->chunk(200, function ($sections): void {
                foreach ($sections as $section) {
                    if (trim($section->room) === '') {
                        continue;
                    }

                    DB::table('rooms')->insertOrIgnore([
                        'name' => $section->room,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
