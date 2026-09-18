<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->enum('school_level', ['Junior High School', 'Senior High School'])
                ->default('Junior High School')
                ->after('subject_ID');
        });

        // Existing cluster-specific subjects are senior high; unclustered ones
        // are junior high.  The new column makes that distinction explicit.
        DB::table('subjects')
            ->update([
                'school_level' => DB::raw("CASE WHEN cluster_ID IS NULL THEN 'Junior High School' ELSE 'Senior High School' END"),
            ]);

        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropForeign(['cluster_ID']);
            $table->dropColumn('cluster_ID');
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->enum('school_level', ['Junior High School', 'Senior High School'])->default('Junior High School')->change();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->unsignedInteger('cluster_ID')->nullable()->after('subject_ID');
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->foreign('cluster_ID')->references('cluster_ID')->on('clusters')->nullOnDelete();
            $table->dropColumn('school_level');
        });
    }
};
