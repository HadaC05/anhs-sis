<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\PreferredCourse;
use Illuminate\Database\Seeder;
use RuntimeException;

class PreferredCourseSeeder extends Seeder
{
    /**
     * Seed the application's preferred_courses table.
     */
    public function run(): void
    {
        $clusterNames = [
            'Arts, Social Sciences & Humanities',
            'Business and Entrepreneurship',
            'Science, Technology, Engineering and Mathematics',
        ];

        $clusterMap = Cluster::query()
            ->whereIn('name', $clusterNames)
            ->pluck('cluster_ID', 'name');

        foreach ($clusterNames as $clusterName) {
            if (! isset($clusterMap[$clusterName])) {
                throw new RuntimeException("Missing cluster: {$clusterName}. Run ClusterSeeder first.");
            }
        }

        $courses = [
            ['cluster' => 'Arts, Social Sciences & Humanities', 'name' => 'Lawyer'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'name' => 'Psychology'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'name' => 'Social Work'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'name' => 'Teachers'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'name' => 'Travel Attendants & Stewards'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'name' => 'Uniformed Service Professions (Police, Army, Navy, Airforce, Fire, others)'],

            ['cluster' => 'Business and Entrepreneurship', 'name' => 'Business/Accountancy-related courses'],
            ['cluster' => 'Business and Entrepreneurship', 'name' => 'Culinary Arts & Hospitality-related courses'],
            ['cluster' => 'Business and Entrepreneurship', 'name' => 'Agriculture'],

            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Medical Doctor'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Nursing/ Midwifery'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Medtech/Radtech'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Architect'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Engineering'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Aviation-related careers'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Maritime-related careers'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Computer-related careers'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'name' => 'Others (please specify)'],
        ];

        foreach ($courses as $course) {
            PreferredCourse::query()->updateOrCreate(
                [
                    'cluster_ID' => $clusterMap[$course['cluster']],
                    'name' => $course['name'],
                ],
                [
                    'description' => $course['description'] ?? null,
                ],
            );
        }
    }
}
