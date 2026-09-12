<?php

use App\Models\Role;
use App\Models\Staff;
use Database\Seeders\DefaultNonStudentUsersSeeder;
use Database\Seeders\RoleSeeder;

test('staff seeder creates one teacher account for each advisory section', function () {
    $this->seed([
        RoleSeeder::class,
        DefaultNonStudentUsersSeeder::class,
    ]);

    $teacherRoleId = Role::query()->where('role_name', 'teacher')->value('id');
    $teacherUsernames = array_values(DefaultNonStudentUsersSeeder::sectionTeacherUsernames());

    expect(Staff::query()->where('role_id', $teacherRoleId)->count())->toBe(30)
        ->and($teacherUsernames)->toHaveCount(30)
        ->and(Staff::query()->pluck('username')->all())->toContain(
            'admin',
            'teacher',
            'teacher_7b',
            'teacher_8',
            'teacher_8b',
            'teacher_9',
            'teacher_10',
            'teacher_11',
            'teacher_12',
            'teacher_12e',
            'guidance_counselor',
            'registrar',
            'principal',
        );

    foreach (DefaultNonStudentUsersSeeder::sectionTeacherUsernames() as $username) {
        $teacher = Staff::query()->where('username', $username)->first();

        expect($teacher)->not->toBeNull()
            ->and($teacher->role_id)->toBe($teacherRoleId)
            ->and($teacher->email)->toBe($username.'@example.com')
            ->and($teacher->status)->toBe('active');
    }

    expect(Staff::query()->where('role_id', $teacherRoleId)->pluck('employee_no')->unique()->count())
        ->toBe(30);
});
