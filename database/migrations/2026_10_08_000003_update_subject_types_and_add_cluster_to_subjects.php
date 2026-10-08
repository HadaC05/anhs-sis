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
            $table->unsignedInteger('cluster_ID')->nullable()->after('school_level');
            $table->foreign('cluster_ID')->references('cluster_ID')->on('clusters')->restrictOnDelete();
        });

        $now = now();
        DB::table('subject_types')->insertOrIgnore([
            ['key' => 'general', 'label' => 'General', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'elective', 'label' => 'Elective', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('subject_types')->where('key', 'core')->update(['sort_order' => 2, 'updated_at' => $now]);

        $typeIds = DB::table('subject_types')->pluck('subject_type_ID', 'key');

        DB::table('subjects')->where('school_level', 'Junior High School')->update([
            'subject_type_ID' => $typeIds['general'],
            'cluster_ID' => null,
        ]);

        $obsoleteTypeIds = DB::table('subject_types')
            ->whereNotIn('key', ['general', 'core', 'elective'])
            ->pluck('subject_type_ID');

        if ($obsoleteTypeIds->isNotEmpty()) {
            DB::table('subjects')->whereIn('subject_type_ID', $obsoleteTypeIds)->update([
                'subject_type_ID' => $typeIds['elective'],
            ]);
        }

        $legacyClusters = [
            'Arts, Social Sciences & Humanities' => ['PRACTRESEARCH1', 'PRACTRESEARCH2', 'INQUIRY', 'FILIPINO', 'CULMINATING', 'DISS', 'DIASS', 'CREATIVEWRITING', 'PPG'],
            'Business and Entrepreneurship' => ['ACCOUNTING', 'BUSMATH', 'ORGMGMT', 'APPECON'],
            'Science, Technology, Engineering and Mathematics' => ['IMMTECH', 'PRECALC', 'BASICCALC', 'GENBIO1', 'GENBIO2', 'GENCHEM1', 'GENCHEM2', 'GENPHYS1', 'GENPHYS2'],
        ];

        foreach ($legacyClusters as $clusterName => $codes) {
            $clusterId = DB::table('clusters')->where('name', $clusterName)->value('cluster_ID');

            if ($clusterId) {
                DB::table('subjects')->whereIn('code', $codes)->update(['cluster_ID' => $clusterId]);
            }
        }

        DB::table('subject_types')->whereNotIn('key', ['general', 'core', 'elective'])->delete();
    }

    public function down(): void
    {
        $now = now();
        DB::table('subject_types')->insertOrIgnore([
            ['key' => 'applied', 'label' => 'Applied', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'specialized', 'label' => 'Specialized', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $typeIds = DB::table('subject_types')->pluck('subject_type_ID', 'key');
        DB::table('subjects')->where('subject_type_ID', $typeIds['general'])->update(['subject_type_ID' => $typeIds['core']]);
        DB::table('subjects')->where('subject_type_ID', $typeIds['elective'])->update(['subject_type_ID' => $typeIds['specialized']]);
        DB::table('subject_types')->whereIn('key', ['general', 'elective'])->delete();

        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropForeign(['cluster_ID']);
            $table->dropColumn('cluster_ID');
        });
    }
};
