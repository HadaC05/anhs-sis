<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\Subject;
use App\Models\SubjectType;
use Illuminate\Database\Seeder;
use RuntimeException;

class SubjectSeeder extends Seeder
{
    /** @var array<string, string> */
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

    /** @var array<string, string> */
    public const SENIOR_HIGH_CORE_SUBJECTS = [
        'EFFCOM' => 'Effective Communication & Mabisang Communication',
        'GENSCI' => 'General Science',
        'GENMATH' => 'General Mathematics',
        'LIFECAR' => 'Life and Career Skills',
        'PHHISTSOC' => 'Philippine History and Society',
    ];

    /** @var array<string, string> */
    public const EFFECTIVE_COMMUNICATION_COMPONENTS = [
        'EFFCOM-ENG' => 'Effective Communication',
        'EFFCOM-FIL' => 'Mabisang Communication',
    ];

    /** @var array<string, list<string>> */
    public const ELECTIVES_BY_CLUSTER = [
        'Arts, Social Sciences & Humanities' => [
            'Art Criticism and Creative Markets',
            'Creative Industries - Visual Arts',
            'Creative Industries - Literary Arts',
            'Creative Industries - Media Arts',
            'Creative Industries - Applied and Traditional Arts',
            'Creative Industries - Music',
            'Creative Industries - Dance',
            'Creative Industries - Theater Arts',
            'Citizenship and Civic Engagement',
            'Contemporary Literature 1',
            'Contemporary Literature 2',
            'Creative Composition 1',
            'Creative Composition 2',
            'Creative Production and Presentation',
            'Filipino 1 (Wika at Komunikasyon sa Akademikong Filipino)',
            'Filipino 2 (Filipino sa Isports)',
            'Filipino 2 (Filipino sa Sining at Disenyo)',
            'Filipino 2 (Filipino sa Larang Teknikal-Propesyonal)',
            'Filipino Identity Through the Arts',
            'Introduction to the Philosophy of the Human Person',
            'Leadership and Management in the Arts',
            'Malikhaing Pagsulat',
            'Performance Criticism and Creative Markets',
            'Philippine Governance (Philippine Politics and Governance)',
            'Social Sciences (Theory and Practice)',
        ],
        'Business and Entrepreneurship' => [
            'Business 1 (Basic Accounting)',
            'Business 2 (Business Finance and Income Taxation)',
            'Business 3 (Business Economics)',
            'Contemporary Marketing',
            'Entrepreneurship',
            'Introduction to Organization and Management',
        ],
        'Science, Technology, Engineering and Mathematics' => [
            'Advanced Mathematics',
            'Basic Calculus',
            'Biology 1',
            'Biology 2',
            'Biology 3',
            'Biology 4',
            'Chemistry 1',
            'Chemistry 2',
            'Chemistry 3',
            'Chemistry 4',
            'Conceptual Physics and Chemistry in Daily Life',
            'Conceptual Biology and Earth and Space Science',
            'Database Management',
            'Earth and Space Science 1',
            'Earth and Space Science 2',
            'Earth and Space Science 3',
            'Earth and Space Science 4',
            'Empowerment Technologies',
            'Finite Mathematics 1',
            'Finite Mathematics 2',
            'Fundamentals of Data Analytics',
            'Physics 1',
            'Physics 2',
            'Physics 3',
            'Physics 4',
            'Pre-Calculus',
        ],
        'Sports, Health, and Wellness' => [
            'Exercise and Sports Programming',
            'First Aid',
            'Fundamentals of Basic Life Support',
            'Human Movement 1 (Basic Anatomy in Sports and Exercise)',
            'Human Movement 2 (Motor Skills Development)',
            'Physical Education 1 (Fitness and Recreation)',
            'Physical Education 2 (Sports and Dance)',
            'Sports Activity Management',
            'Sports Coaching',
            'Sports Officiating',
        ],
        'ICT Support and Computer Programming Technologies' => [
            'Broadband Installation',
            'Computer Programming (Java)',
            'Computer Programming (.Net Technology)',
            'Computer Programming (Oracle Database)',
            'Computer Systems Servicing',
            'Contact Center Services',
        ],
        'Aesthetic, Wellness, and Human Care' => [
            'Aesthetic Services (Beauty Care)',
            'Caregiving (Adult Care)',
            'Caregiving (Child Care)',
            'Hairdressing Services',
        ],
        'Agri-Fishery Business and Food Innovation' => [
            'Agricultural Crops Production',
            'Agro-entrepreneurship',
            'Aquaculture',
            'Fish Capture Operation',
            'Food Processing',
            'Organic Agriculture Production',
            'Poultry Production (Chicken)',
            'Ruminants Production',
            'Swine Production',
        ],
        'Artisanal and Creative Enterprise' => [
            'Garments Artisanry',
            'Handicrafts: Weaving',
        ],
        'Automotive and Small Engine Technologies' => [
            'Automotive Servicing (Electrical Repair)',
            'Automotive Servicing (Engine and Chassis Repairs)',
            'Driving and Automotive Servicing',
            'Motorcycle and Small Engine Servicing',
        ],
        'Construction and Building Technologies' => [
            'Carpentry',
            'Construction Operation',
            'Manual Metal Arc Welding',
            'Technical Drafting',
        ],
        'Creative Arts and Design Technologies' => [
            'Animation',
            'Illustration',
            'Visual Graphic Design',
        ],
        'Hospitality and Tourism' => [
            'Bakery Operations',
            'Events Management Services',
            'Food and Beverage Operation',
            'Hotel Operations (Front Office Services)',
            'Hotel Operations (Housekeeping Services)',
            'Kitchen Operations',
            'Tourism Services',
        ],
        'Industrial Technologies' => [
            'Commercial Air-Conditioning Installation and Servicing',
            'Domestic Refrigeration and Air-Conditioning Servicing',
            'Electrical Installation and Maintenance',
            'Electronic Products Assembly and Servicing',
            'Mechatronics',
            'Photovoltaic Systems Installation',
        ],
    ];

    /** @var list<string> */
    private const LEGACY_SENIOR_HIGH_CODES = [
        'ORALCOM', 'KOMFIL', 'GENMAT', 'STATPROB', 'MIL', 'UCSP', 'RPH', 'EAPP',
        'PAGSULAT', 'HOPE', 'PERDEV', 'ENTREP', 'IMMTECH', 'PRACTRESEARCH1',
        'PRACTRESEARCH2', 'INQUIRY', 'FILIPINO', 'CULMINATING', 'PRECALC',
        'BASICCALC', 'GENBIO1', 'GENBIO2', 'GENCHEM1', 'GENCHEM2', 'GENPHYS1',
        'GENPHYS2', 'ACCOUNTING', 'BUSMATH', 'ORGMGMT', 'APPECON', 'DISS', 'DIASS',
        'CREATIVEWRITING', 'PPG',
    ];

    public function run(): void
    {
        $typeIds = SubjectType::query()->pluck('subject_type_ID', 'key');

        foreach (['general', 'core', 'elective'] as $type) {
            if (! isset($typeIds[$type])) {
                throw new RuntimeException("Missing subject type: {$type}. Run SubjectTypeSeeder first.");
            }
        }

        foreach ([7, 8, 9, 10] as $grade) {
            foreach (self::JUNIOR_HIGH_AREAS as $prefix => $title) {
                $this->seedSubject($prefix.$grade, $title.' '.$grade, 'Junior High School', $typeIds['general']);
            }
        }

        foreach (self::SENIOR_HIGH_CORE_SUBJECTS as $code => $title) {
            $this->seedSubject($code, $title, 'Senior High School', $typeIds['core']);
        }

        foreach (self::EFFECTIVE_COMMUNICATION_COMPONENTS as $code => $title) {
            $this->seedSubject($code, $title, 'Senior High School', $typeIds['core']);
        }

        $clusterIds = Cluster::query()->pluck('cluster_ID', 'name');

        foreach (self::ELECTIVES_BY_CLUSTER as $clusterName => $titles) {
            if (! isset($clusterIds[$clusterName])) {
                throw new RuntimeException("Missing cluster: {$clusterName}. Run ClusterSeeder first.");
            }

            foreach ($titles as $index => $title) {
                $this->seedSubject(
                    self::electiveCodesForCluster($clusterName)[$index],
                    $title,
                    'Senior High School',
                    $typeIds['elective'],
                    $clusterIds[$clusterName],
                );
            }
        }

        Subject::query()->whereIn('code', self::LEGACY_SENIOR_HIGH_CODES)->update(['status' => 'archived']);
    }

    private function seedSubject(string $code, string $title, string $schoolLevel, int $typeId, ?int $clusterId = null): void
    {
        Subject::query()->updateOrCreate(
            ['code' => $code],
            [
                'school_level' => $schoolLevel,
                'cluster_ID' => $clusterId,
                'subject_type_ID' => $typeId,
                'title' => $title,
                'status' => 'active',
            ],
        );
    }

    /** @return list<string> */
    public static function electiveCodesForCluster(string $clusterName): array
    {
        return array_map(
            fn (int $index): string => sprintf('%s-E%02d', self::electiveCodePrefix($clusterName), $index + 1),
            array_keys(self::ELECTIVES_BY_CLUSTER[$clusterName] ?? []),
        );
    }

    private static function electiveCodePrefix(string $clusterName): string
    {
        return match ($clusterName) {
            'Arts, Social Sciences & Humanities' => 'ASSH',
            'Business and Entrepreneurship' => 'BE',
            'Science, Technology, Engineering and Mathematics' => 'STEM',
            'Sports, Health, and Wellness' => 'SHW',
            'ICT Support and Computer Programming Technologies' => 'ICT',
            'Aesthetic, Wellness, and Human Care' => 'AWHC',
            'Agri-Fishery Business and Food Innovation' => 'AFBFI',
            'Artisanal and Creative Enterprise' => 'ACE',
            'Automotive and Small Engine Technologies' => 'AUTO',
            'Construction and Building Technologies' => 'CBT',
            'Creative Arts and Design Technologies' => 'CADT',
            'Hospitality and Tourism' => 'HT',
            'Industrial Technologies' => 'IT',
        };
    }
}
