<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use RuntimeException;

class SubjectSeeder extends Seeder
{
    /**
     * Junior high learning areas keyed by subject code prefix.
     *
     * @var array<string, string>
     */
    public const JUNIOR_HIGH_AREAS = [
        'MATH' => 'Mathematics',
        'SCI' => 'Science',
        'ENG' => 'English',
        'FIL' => 'Filipino',
        'AP' => 'Araling Panlipunan',
        'MAPEH' => 'MAPEH',
        'TLE' => 'TLE',
        'ESP' => 'Edukasyon sa Pagpapakatao',
    ];

    /**
     * Seed the application's subjects table.
     */
    public function run(): void
    {
        $clusterMap = Cluster::query()
            ->whereIn('name', [
                'Arts, Social Sciences & Humanities',
                'Business and Entrepreneurship',
                'Science, Technology, Engineering and Mathematics',
            ])
            ->pluck('cluster_ID', 'name');

        foreach ([
            'Arts, Social Sciences & Humanities',
            'Business and Entrepreneurship',
            'Science, Technology, Engineering and Mathematics',
        ] as $clusterName) {
            if (! isset($clusterMap[$clusterName])) {
                throw new RuntimeException("Missing cluster: {$clusterName}. Run ClusterSeeder first.");
            }
        }

        $subjects = [];

        foreach ([7, 8, 9, 10] as $grade) {
            foreach (self::JUNIOR_HIGH_AREAS as $prefix => $title) {
                $subjects[] = [
                    'cluster' => null,
                    'code' => $prefix.$grade,
                    'title' => $title.' '.$grade,
                    'type' => 'core',
                ];
            }
        }

        $subjects = array_merge($subjects, [
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'ORALCOM', 'title' => 'Oral Communication', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'KOMFIL', 'title' => 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'type' => 'core'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'GENMAT', 'title' => 'General Mathematics', 'type' => 'core'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'STATPROB', 'title' => 'Statistics and Probability', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'MIL', 'title' => 'Media and Information Literacy', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'UCSP', 'title' => 'Understanding Culture, Society and Politics', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'RPH', 'title' => 'Readings in Philippine History', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'EAPP', 'title' => 'English for Academic and Professional Purposes', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'PAGSULAT', 'title' => 'Pagsulat sa Filipino sa Piling Larangan', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'HOPE', 'title' => 'Health Optimizing Physical Education', 'type' => 'core'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'PERDEV', 'title' => 'Personal Development', 'type' => 'core'],
            ['cluster' => 'Business and Entrepreneurship', 'code' => 'ENTREP', 'title' => 'Entrepreneurship', 'type' => 'core'],
            ['cluster' => 'Business and Entrepreneurship', 'code' => 'IMMTECH', 'title' => 'Empowerment Technologies', 'type' => 'applied'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'PRACTRESEARCH1', 'title' => 'Practical Research 1', 'type' => 'applied'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'PRACTRESEARCH2', 'title' => 'Practical Research 2', 'type' => 'applied'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'INQUIRY', 'title' => 'Inquiries, Investigations and Immersion', 'type' => 'applied'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'FILIPINO', 'title' => 'Filipino sa Piling Larangan', 'type' => 'applied'],
            ['cluster' => 'Business and Entrepreneurship', 'code' => 'CULMINATING', 'title' => 'Culminating Activity', 'type' => 'applied'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'PRECALC', 'title' => 'Pre-Calculus', 'type' => 'specialized'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'BASICCALC', 'title' => 'Basic Calculus', 'type' => 'specialized'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'GENBIO1', 'title' => 'General Biology 1', 'type' => 'specialized'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'GENBIO2', 'title' => 'General Biology 2', 'type' => 'specialized'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'GENCHEM1', 'title' => 'General Chemistry 1', 'type' => 'specialized'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'GENCHEM2', 'title' => 'General Chemistry 2', 'type' => 'specialized'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'GENPHYS1', 'title' => 'General Physics 1', 'type' => 'specialized'],
            ['cluster' => 'Science, Technology, Engineering and Mathematics', 'code' => 'GENPHYS2', 'title' => 'General Physics 2', 'type' => 'specialized'],
            ['cluster' => 'Business and Entrepreneurship', 'code' => 'ACCOUNTING', 'title' => 'Fundamentals of Accountancy, Business and Management', 'type' => 'specialized'],
            ['cluster' => 'Business and Entrepreneurship', 'code' => 'BUSMATH', 'title' => 'Business Mathematics', 'type' => 'specialized'],
            ['cluster' => 'Business and Entrepreneurship', 'code' => 'ORGMGMT', 'title' => 'Organization and Management', 'type' => 'specialized'],
            ['cluster' => 'Business and Entrepreneurship', 'code' => 'APPECON', 'title' => 'Applied Economics', 'type' => 'specialized'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'DISS', 'title' => 'Disciplines and Ideas in the Social Sciences', 'type' => 'specialized'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'DIASS', 'title' => 'Disciplines and Ideas in the Applied Social Sciences', 'type' => 'specialized'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'CREATIVEWRITING', 'title' => 'Creative Writing', 'type' => 'specialized'],
            ['cluster' => 'Arts, Social Sciences & Humanities', 'code' => 'PPG', 'title' => 'Philippine Politics and Governance', 'type' => 'specialized'],
        ]);

        foreach ($subjects as $subject) {
            Subject::query()->updateOrCreate(
                ['code' => $subject['code']],
                [
                    'cluster_ID' => $subject['cluster'] ? $clusterMap[$subject['cluster']] : null,
                    'title' => $subject['title'],
                    'type' => $subject['type'],
                ],
            );
        }
    }
}
