<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clusters', function (Blueprint $table) {
            $table->unsignedInteger('track_ID')->nullable()->after('cluster_ID');
            $table->foreign('track_ID')->references('track_ID')->on('tracks')->restrictOnDelete();
        });

        $academicTrackId = DB::table('tracks')
            ->where('name', 'Academic Track')
            ->value('track_ID');

        DB::table('clusters')->update(['track_ID' => $academicTrackId]);
    }

    public function down(): void
    {
        Schema::table('clusters', function (Blueprint $table) {
            $table->dropForeign(['track_ID']);
            $table->dropColumn('track_ID');
        });
    }
};
