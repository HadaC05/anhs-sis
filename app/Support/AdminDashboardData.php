<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class AdminDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(): array
    {
        $staffCount = Staff::query()->count();
        $activeStaffCount = Staff::query()->where('status', 'active')->count();
        $inactiveStaffCount = Staff::query()->where('status', 'inactive')->count();

        $studentCount = Student::query()->count();
        $studentAccountCount = Student::query()
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->count();
        $totalUsers = $staffCount + $studentAccountCount;

        $usersByRole = DB::table('staffs')
            ->join('roles', 'staffs.role_id', '=', 'roles.id')
            ->selectRaw('roles.role_name as label')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('roles.role_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => ucfirst($row->label),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        if ($studentAccountCount > 0) {
            $usersByRole[] = [
                'label' => 'Student accounts',
                'total' => $studentAccountCount,
            ];
        }

        usort($usersByRole, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        $staffStatusDistribution = [
            ['label' => 'Active', 'total' => $activeStaffCount],
            ['label' => 'Inactive', 'total' => $inactiveStaffCount],
        ];

        return [
            'totalUsers' => $totalUsers,
            'staffCount' => $staffCount,
            'studentCount' => $studentCount,
            'studentAccountCount' => $studentAccountCount,
            'activeStaffCount' => $activeStaffCount,
            'inactiveStaffCount' => $inactiveStaffCount,
            'usersByRole' => $usersByRole,
            'staffStatusDistribution' => $staffStatusDistribution,
        ];
    }
}
