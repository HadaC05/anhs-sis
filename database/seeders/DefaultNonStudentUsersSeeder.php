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
     * Seed default staff accounts for all non-student roles.
     */
    public function run(): void
    {
        $roles = Role::query()
            ->where('role_name', '!=', 'student')
            ->get();

        foreach ($roles as $role) {
            $slug = Str::slug($role->role_name, '_');

            $profile = $this->dummyProfileForRole($role->role_name);

            Staff::query()->updateOrCreate(
                ['username' => $slug],
                [
                    'role_id' => $role->id,
                    'first_name' => $profile['first_name'],
                    'middle_name' => $profile['middle_name'],
                    'last_name' => $profile['last_name'],
                    'suffix' => $profile['suffix'],
                    'gender' => $profile['gender'],
                    'birthdate' => $profile['birthdate'],
                    'email' => $slug . '@example.com',
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
    }

    /**
     * Get deterministic dummy staff profile data per role.
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
            'teacher' => [
                'first_name' => 'Maria',
                'middle_name' => 'L.',
                'last_name' => 'Santos',
                'suffix' => null,
                'gender' => 'female',
                'birthdate' => '1990-07-21',
                'appointment_status' => 'permanent',
                'fund_source' => 'government',
                'degree_earned' => 'Bachelor of Secondary Education',
                'major_specialization' => 'Mathematics',
                'teaching_minutes' => 1080,
                'employee_no' => 'EMP-TEACHER',
                'plantilla_item_no' => 'PLN-TEACHER',
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
                'employee_no' => 'EMP-' . Str::upper(Str::slug($roleName, '-')),
                'plantilla_item_no' => 'PLN-' . Str::upper(Str::slug($roleName, '-')),
            ],
        };
    }
}
