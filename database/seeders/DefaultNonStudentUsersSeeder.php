<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultNonStudentUsersSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const GRADE_LEVELS = ['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'];

    /**
     * @var list<string>
     */
    public const SECTION_LETTERS = ['A', 'B', 'C', 'D', 'E'];

    /**
     * Seed default staff accounts for all non-student roles.
     */
    public function run(): void
    {
        $roles = Role::query()->get();

        foreach ($roles as $role) {
            if ($role->role_name === 'teacher') {
                $this->seedSectionTeachers($role);

                continue;
            }

            $this->seedStaff($role->id, Str::slug($role->role_name, '_'), $this->dummyProfileForRole($role->role_name));
        }
    }

    /**
     * Default teacher username for a grade-and-letter section.
     */
    public static function teacherUsernameFor(string $gradeLevel, string $letter): string
    {
        $grade = str_replace('grade_', '', $gradeLevel);

        if ($letter === 'A') {
            return $grade === '7' ? 'teacher' : 'teacher_'.$grade;
        }

        return 'teacher_'.$grade.strtolower($letter);
    }

    /**
     * Seeded section name for a grade-and-letter pair.
     */
    public static function sectionNameFor(string $gradeLevel, string $letter): string
    {
        return strtoupper(str_replace('grade_', 'G', $gradeLevel)).'-'.$letter;
    }

    /**
     * Default teacher usernames keyed by section name.
     *
     * @return array<string, string>
     */
    public static function sectionTeacherUsernames(): array
    {
        $usernames = [];

        foreach (self::GRADE_LEVELS as $gradeLevel) {
            foreach (self::SECTION_LETTERS as $letter) {
                $usernames[self::sectionNameFor($gradeLevel, $letter)] = self::teacherUsernameFor($gradeLevel, $letter);
            }
        }

        foreach (self::seniorHighSectionDefinitions() as $section) {
            $usernames[$section['name']] = self::seniorHighTeacherUsername($section);
        }

        return $usernames;
    }

    /**
     * The default SHS section for every grade, cluster, and semester.
     *
     * @return list<array{name: string, grade_level: string, track: string, semester: 'first'|'second'}>
     */
    public static function seniorHighSectionDefinitions(): array
    {
        $sections = [];

        foreach ([11, 12] as $grade) {
            foreach (CurriculumSeeder::SENIOR_HIGH_TRACKS as $track) {
                foreach (['first', 'second'] as $semester) {
                    $sections[] = [
                        'name' => self::seniorHighSectionName($grade, $track, $semester),
                        'grade_level' => 'grade_'.$grade,
                        'track' => $track,
                        'semester' => $semester,
                    ];
                }
            }
        }

        return $sections;
    }

    /** @param array{name: string, grade_level: string, track: string, semester: string} $section */
    public static function seniorHighTeacherUsername(array $section): string
    {
        return 'teacher_'.strtolower(str_replace('-', '_', $section['name']));
    }

    public static function seniorHighSectionName(int $grade, string $track, string $semester): string
    {
        return sprintf('G%d-%s-%s', $grade, self::clusterCode($track), $semester === 'first' ? '1ST' : '2ND');
    }

    private function seedSectionTeachers(Role $role): void
    {
        foreach (self::GRADE_LEVELS as $gradeLevel) {
            $grade = (int) str_replace('grade_', '', $gradeLevel);

            foreach (self::SECTION_LETTERS as $letter) {
                $this->seedStaff(
                    $role->id,
                    self::teacherUsernameFor($gradeLevel, $letter),
                    $this->dummyProfileForSectionTeacher($grade, $letter),
                );
            }
        }

        foreach (self::seniorHighSectionDefinitions() as $index => $section) {
            $this->seedStaff(
                $role->id,
                self::seniorHighTeacherUsername($section),
                $this->dummyProfileForSeniorHighSection($section, $index),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function seedStaff(int $roleId, string $username, array $profile): Staff
    {
        return Staff::query()->updateOrCreate(
            ['username' => $username],
            [
                'role_id' => $roleId,
                'first_name' => $profile['first_name'],
                'middle_name' => $profile['middle_name'],
                'last_name' => $profile['last_name'],
                'suffix' => $profile['suffix'],
                'gender' => $profile['gender'],
                'birthdate' => $profile['birthdate'],
                'email' => $username.'@example.com',
                'password' => Hash::make('password'),
                'change_password' => false,
                'status' => 'active',
                'employee_no' => $profile['employee_no'],
                'plantilla_item_no' => $profile['plantilla_item_no'],
                'appointment_status' => $profile['appointment_status'],
                'fund_source' => $profile['fund_source'],
                'degree_earned' => $profile['degree_earned'],
                'major_specialization' => $profile['major_specialization'],
                'teaching_minutes' => $profile['teaching_minutes'],
            ]
        );
    }

    /**
     * Get deterministic dummy staff profile data per role.
     *
     * @return array<string, mixed>
     */
    private function dummyProfileForRole(string $roleName): array
    {
        return match ($roleName) {
            'admin' => [
                'first_name' => 'Alex',
                'middle_name' => 'M.',
                'last_name' => 'Rivera',
                'suffix' => null,
                'gender' => 'male',
                'birthdate' => '1988-03-12',
                'appointment_status' => 'permanent',
                'fund_source' => 'government',
                'degree_earned' => 'Bachelor of Science in Information Technology',
                'major_specialization' => 'Systems Administration',
                'teaching_minutes' => 0,
                'employee_no' => 'EMP-ADMIN',
                'plantilla_item_no' => 'PLN-ADMIN',
            ],
            'guidance counselor' => [
                'first_name' => 'Carlo',
                'middle_name' => 'D.',
                'last_name' => 'Reyes',
                'suffix' => null,
                'gender' => 'male',
                'birthdate' => '1989-11-03',
                'appointment_status' => 'contractual',
                'fund_source' => 'government',
                'degree_earned' => 'Master of Arts in Guidance and Counseling',
                'major_specialization' => 'Student Counseling',
                'teaching_minutes' => 300,
                'employee_no' => 'EMP-GUIDANCE',
                'plantilla_item_no' => 'PLN-GUIDANCE',
            ],
            'registrar' => [
                'first_name' => 'Elaine',
                'middle_name' => 'P.',
                'last_name' => 'Garcia',
                'suffix' => null,
                'gender' => 'female',
                'birthdate' => '1992-02-14',
                'appointment_status' => 'permanent',
                'fund_source' => 'government',
                'degree_earned' => 'Bachelor of Science in Office Administration',
                'major_specialization' => 'Records Management',
                'teaching_minutes' => 0,
                'employee_no' => 'EMP-REGISTRAR',
                'plantilla_item_no' => 'PLN-REGISTRAR',
            ],
            'principal' => [
                'first_name' => 'Ramon',
                'middle_name' => 'T.',
                'last_name' => 'Delos Santos',
                'suffix' => null,
                'gender' => 'male',
                'birthdate' => '1979-09-05',
                'appointment_status' => 'permanent',
                'fund_source' => 'government',
                'degree_earned' => 'Doctor of Education',
                'major_specialization' => 'Educational Leadership',
                'teaching_minutes' => 120,
                'employee_no' => 'EMP-PRINCIPAL',
                'plantilla_item_no' => 'PLN-PRINCIPAL',
            ],
            default => [
                'first_name' => 'Default',
                'middle_name' => null,
                'last_name' => Str::title($roleName),
                'suffix' => null,
                'gender' => 'male',
                'birthdate' => '1990-01-01',
                'appointment_status' => 'job_order',
                'fund_source' => 'other',
                'degree_earned' => 'Bachelor Degree',
                'major_specialization' => 'General',
                'teaching_minutes' => 0,
                'employee_no' => 'EMP-'.Str::upper(Str::slug($roleName, '-')),
                'plantilla_item_no' => 'PLN-'.Str::upper(Str::slug($roleName, '-')),
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dummyProfileForSectionTeacher(int $grade, string $letter): array
    {
        $identity = $this->sectionTeacherIdentity($grade, $letter);

        $employeeSuffix = $letter === 'A' && $grade === 7
            ? 'TEACHER'
            : 'TEACHER-'.($letter === 'A' ? (string) $grade : $grade.$letter);

        return [
            'first_name' => $identity['first_name'],
            'middle_name' => $identity['middle_name'],
            'last_name' => $identity['last_name'],
            'suffix' => null,
            'gender' => $identity['gender'],
            'birthdate' => $identity['birthdate'],
            'appointment_status' => 'permanent',
            'fund_source' => 'government',
            'degree_earned' => 'Bachelor of Secondary Education',
            'major_specialization' => $this->specializationForLetter($grade, $letter),
            'teaching_minutes' => 1080,
            'employee_no' => 'EMP-'.$employeeSuffix,
            'plantilla_item_no' => 'PLN-'.$employeeSuffix,
        ];
    }

    /**
     * @param  array{name: string, grade_level: string, track: string, semester: string}  $section
     * @return array<string, mixed>
     */
    private function dummyProfileForSeniorHighSection(array $section, int $index): array
    {
        $grade = (int) str_replace('grade_', '', $section['grade_level']);
        $identity = $this->sectionTeacherIdentity($grade, self::SECTION_LETTERS[$index % count(self::SECTION_LETTERS)]);
        $employeeSuffix = str_replace('-', '-', strtoupper($section['name']));

        return [
            'first_name' => $identity['first_name'],
            'middle_name' => $identity['middle_name'],
            'last_name' => $identity['last_name'],
            'suffix' => null,
            'gender' => $identity['gender'],
            'birthdate' => $identity['birthdate'],
            'appointment_status' => 'permanent',
            'fund_source' => 'government',
            'degree_earned' => 'Bachelor of Secondary Education',
            'major_specialization' => $section['track'].' ('.ucfirst($section['semester']).' Semester)',
            'teaching_minutes' => 1080,
            'employee_no' => 'EMP-TEACHER-'.$employeeSuffix,
            'plantilla_item_no' => 'PLN-TEACHER-'.$employeeSuffix,
        ];
    }

    private function specializationForLetter(int $grade, string $letter): string
    {
        return match ($letter) {
            'A' => 'Mathematics',
            'B' => $grade === 12 ? 'Research' : 'Science',
            'C' => 'English',
            'D' => 'Filipino',
            'E' => $grade >= 11 ? 'Social Science' : 'Araling Panlipunan',
            default => 'General',
        };
    }

    /**
     * @return array{first_name: string, middle_name: string, last_name: string, gender: string, birthdate: string}
     */
    private function sectionTeacherIdentity(int $grade, string $letter): array
    {
        $identities = [
            7 => [
                'A' => ['Maria', 'L.', 'Santos', 'female', '1990-07-21'],
                'B' => ['Rafael', 'N.', 'Bautista', 'male', '1988-03-14'],
                'C' => ['Elena', 'F.', 'Castillo', 'female', '1992-11-05'],
                'D' => ['Gabriel', 'P.', 'Navarro', 'male', '1985-06-19'],
                'E' => ['Sophia', 'J.', 'Domingo', 'female', '1993-02-28'],
            ],
            8 => [
                'A' => ['Jose', 'R.', 'Cruz', 'male', '1987-04-18'],
                'B' => ['Andrea', 'M.', 'Flores', 'female', '1991-08-09'],
                'C' => ['Victor', 'S.', 'Ramos', 'male', '1986-01-22'],
                'D' => ['Camille', 'D.', 'Aquino', 'female', '1994-10-03'],
                'E' => ['Nathan', 'I.', 'Gutierrez', 'male', '1989-12-15'],
            ],
            9 => [
                'A' => ['Ana', 'C.', 'Mendoza', 'female', '1991-09-08'],
                'B' => ['Luis', 'E.', 'Francisco', 'male', '1984-05-27'],
                'C' => ['Patricia', 'H.', 'Lim', 'female', '1993-07-11'],
                'D' => ['Oscar', 'V.', 'Padilla', 'male', '1988-02-06'],
                'E' => ['Bianca', 'K.', 'Santiago', 'female', '1990-11-19'],
            ],
            10 => [
                'A' => ['Paolo', 'S.', 'Villanueva', 'male', '1986-12-02'],
                'B' => ['Hannah', 'G.', 'Dela Cruz', 'female', '1992-04-25'],
                'C' => ['Marco', 'J.', 'Fernandez', 'male', '1987-08-30'],
                'D' => ['Irene', 'T.', 'Magno', 'female', '1989-03-17'],
                'E' => ['Dominic', 'Y.', 'Salazar', 'male', '1991-06-08'],
            ],
            11 => [
                'A' => ['Kristine', 'A.', 'Ramos', 'female', '1989-05-16'],
                'B' => ['Francis', 'O.', 'Tan', 'male', '1985-09-21'],
                'C' => ['Joyce', 'P.', 'Villamor', 'female', '1993-01-04'],
                'D' => ['Enrico', 'L.', 'Soriano', 'male', '1986-07-13'],
                'E' => ['Angela', 'W.', 'Chua', 'female', '1990-10-29'],
            ],
            12 => [
                'A' => ['Miguel', 'B.', 'Torres', 'male', '1985-01-27'],
                'B' => ['Clarisse', 'Q.', 'Javier', 'female', '1991-02-14'],
                'C' => ['Adrian', 'U.', 'Medina', 'male', '1988-11-23'],
                'D' => ['Nicole', 'R.', 'Pascual', 'female', '1994-06-07'],
                'E' => ['Benjamin', 'C.', 'Herrera', 'male', '1987-03-09'],
            ],
        ];

        $identity = $identities[$grade][$letter] ?? ['Teacher', null, 'Grade '.$grade.$letter, 'male', '1990-01-01'];

        return [
            'first_name' => $identity[0],
            'middle_name' => $identity[1],
            'last_name' => $identity[2],
            'gender' => $identity[3],
            'birthdate' => $identity[4],
        ];
    }

    private static function clusterCode(string $cluster): string
    {
        return match ($cluster) {
            'Arts, Social Sciences & Humanities' => 'ASSH',
            'Business and Entrepreneurship' => 'BUS',
            'Science, Technology, Engineering and Mathematics' => 'STEM',
            default => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $cluster) ?: 'SEC', 0, 4)),
        };
    }
}
