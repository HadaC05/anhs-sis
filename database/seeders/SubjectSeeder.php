<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

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
        $subjects = [];

        foreach ([7, 8, 9, 10] as $grade) {
            foreach (self::JUNIOR_HIGH_AREAS as $prefix => $title) {
                $subjects[] = [
                    'school_level' => 'Junior High School',
                    'code' => $prefix.$grade,
                    'title' => $title.' '.$grade,
                    'type' => 'core',
                ];
            }
        }

        $subjects = array_merge($subjects, [
            ['school_level' => 'Senior High School', 'code' => 'ORALCOM', 'title' => 'Oral Communication', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'KOMFIL', 'title' => 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'GENMAT', 'title' => 'General Mathematics', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'STATPROB', 'title' => 'Statistics and Probability', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'MIL', 'title' => 'Media and Information Literacy', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'UCSP', 'title' => 'Understanding Culture, Society and Politics', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'RPH', 'title' => 'Readings in Philippine History', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'EAPP', 'title' => 'English for Academic and Professional Purposes', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'PAGSULAT', 'title' => 'Pagsulat sa Filipino sa Piling Larangan', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'HOPE', 'title' => 'Health Optimizing Physical Education', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'PERDEV', 'title' => 'Personal Development', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'ENTREP', 'title' => 'Entrepreneurship', 'type' => 'core'],
            ['school_level' => 'Senior High School', 'code' => 'IMMTECH', 'title' => 'Empowerment Technologies', 'type' => 'applied'],
            ['school_level' => 'Senior High School', 'code' => 'PRACTRESEARCH1', 'title' => 'Practical Research 1', 'type' => 'applied'],
            ['school_level' => 'Senior High School', 'code' => 'PRACTRESEARCH2', 'title' => 'Practical Research 2', 'type' => 'applied'],
            ['school_level' => 'Senior High School', 'code' => 'INQUIRY', 'title' => 'Inquiries, Investigations and Immersion', 'type' => 'applied'],
            ['school_level' => 'Senior High School', 'code' => 'FILIPINO', 'title' => 'Filipino sa Piling Larangan', 'type' => 'applied'],
            ['school_level' => 'Senior High School', 'code' => 'CULMINATING', 'title' => 'Culminating Activity', 'type' => 'applied'],
            ['school_level' => 'Senior High School', 'code' => 'PRECALC', 'title' => 'Pre-Calculus', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'BASICCALC', 'title' => 'Basic Calculus', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'GENBIO1', 'title' => 'General Biology 1', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'GENBIO2', 'title' => 'General Biology 2', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'GENCHEM1', 'title' => 'General Chemistry 1', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'GENCHEM2', 'title' => 'General Chemistry 2', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'GENPHYS1', 'title' => 'General Physics 1', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'GENPHYS2', 'title' => 'General Physics 2', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'ACCOUNTING', 'title' => 'Fundamentals of Accountancy, Business and Management', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'BUSMATH', 'title' => 'Business Mathematics', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'ORGMGMT', 'title' => 'Organization and Management', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'APPECON', 'title' => 'Applied Economics', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'DISS', 'title' => 'Disciplines and Ideas in the Social Sciences', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'DIASS', 'title' => 'Disciplines and Ideas in the Applied Social Sciences', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'CREATIVEWRITING', 'title' => 'Creative Writing', 'type' => 'specialized'],
            ['school_level' => 'Senior High School', 'code' => 'PPG', 'title' => 'Philippine Politics and Governance', 'type' => 'specialized'],
        ]);

        foreach ($subjects as $subject) {
            Subject::query()->updateOrCreate(
                ['code' => $subject['code']],
                [
                    'school_level' => $subject['school_level'],
                    'title' => $subject['title'],
                    'type' => $subject['type'],
                ],
            );
        }
    }
}
