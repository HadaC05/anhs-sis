<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $targetClusters = [
            'Arts, Social Sciences & Humanities',
            'Business and Entrepreneurship',
            'Science, Technology, Engineering and Mathematics',
        ];

        $existing = DB::table('clusters')->get(['cluster_ID', 'name']);
        $byNormalized = [];
        foreach ($existing as $cluster) {
            $byNormalized[$this->normalize($cluster->name)] = $cluster;
        }

        $targetIds = [];
        foreach ($targetClusters as $name) {
            $normalized = $this->normalize($name);
            if (isset($byNormalized[$normalized])) {
                $cluster = $byNormalized[$normalized];
                if ($cluster->name !== $name) {
                    DB::table('clusters')
                        ->where('cluster_ID', $cluster->cluster_ID)
                        ->update(['name' => $name]);
                }
                $targetIds[$name] = $cluster->cluster_ID;
            } else {
                $id = DB::table('clusters')->insertGetId(['name' => $name]);
                $targetIds[$name] = $id;
            }
        }

        $defaultTargetId = $targetIds['Science, Technology, Engineering and Mathematics'];

        $nameToTarget = [
            'core subjects' => 'Science, Technology, Engineering and Mathematics',
            'applied subjects' => 'Science, Technology, Engineering and Mathematics',
            'specialized subjects' => 'Science, Technology, Engineering and Mathematics',
            'academic track - abm' => 'Business and Entrepreneurship',
            'academic track - stem' => 'Science, Technology, Engineering and Mathematics',
            'academic track - humss' => 'Arts, Social Sciences & Humanities',
            'academic track - gas' => 'Arts, Social Sciences & Humanities',
            'tvl track - agri-fishery arts' => 'Science, Technology, Engineering and Mathematics',
            'tvl track - home economics' => 'Business and Entrepreneurship',
            'tvl track - ict' => 'Science, Technology, Engineering and Mathematics',
            'tvl track - industrial arts' => 'Science, Technology, Engineering and Mathematics',
            'arts and design track' => 'Arts, Social Sciences & Humanities',
            'sports track' => 'Arts, Social Sciences & Humanities',
            'arts, social sciences, and humanities' => 'Arts, Social Sciences & Humanities',
            'business and entrepreneurship' => 'Business and Entrepreneurship',
            'science, technology, engineering, and mathematics' => 'Science, Technology, Engineering and Mathematics',
            'arts, social sciences & humanities' => 'Arts, Social Sciences & Humanities',
            'science, technology, engineering and mathematics' => 'Science, Technology, Engineering and Mathematics',
        ];

        $currentClusters = DB::table('clusters')->get(['cluster_ID', 'name']);
        $clusterMap = [];
        foreach ($currentClusters as $cluster) {
            $normalized = $this->normalize($cluster->name);
            $targetName = $nameToTarget[$normalized] ?? null;
            $targetId = $targetName ? $targetIds[$targetName] : $defaultTargetId;
            $clusterMap[$cluster->cluster_ID] = $targetId;
        }

        $this->dedupePreferredCourses($clusterMap);

        foreach (['subjects', 'curriculum_subjects', 'preferred_courses', 'sections', 'enrollments'] as $table) {
            foreach ($clusterMap as $fromId => $toId) {
                if ($fromId === $toId) {
                    continue;
                }
                DB::table($table)
                    ->where('cluster_ID', $fromId)
                    ->update(['cluster_ID' => $toId]);
            }
        }

        DB::table('clusters')
            ->whereNotIn('cluster_ID', array_values($targetIds))
            ->delete();
    }

    public function down(): void
    {
        //
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value);
        return $value ?? '';
    }

    private function dedupePreferredCourses(array $clusterMap): void
    {
        $targetToOld = [];
        foreach ($clusterMap as $fromId => $toId) {
            $targetToOld[$toId][] = $fromId;
        }

        foreach ($targetToOld as $targetId => $oldIds) {
            $duplicates = DB::table('preferred_courses')
                ->select('name', DB::raw('MIN(course_ID) as keep_id'), DB::raw('COUNT(*) as total'))
                ->whereIn('cluster_ID', $oldIds)
                ->groupBy('name')
                ->having('total', '>', 1)
                ->get();

            foreach ($duplicates as $row) {
                DB::table('preferred_courses')
                    ->whereIn('cluster_ID', $oldIds)
                    ->where('name', $row->name)
                    ->where('course_ID', '!=', $row->keep_id)
                    ->delete();
            }
        }
    }
};
