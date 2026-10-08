<?php

use App\Models\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_types')->updateOrInsert(
            ['slug' => NotificationType::SUBJECTS_AWAITING_RELEASE],
            [
                'name' => 'Subjects awaiting release',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('notification_types')
            ->where('slug', NotificationType::ACADEMIC_SUPPORT)
            ->update(['sort_order' => 11, 'updated_at' => now()]);
        NotificationType::clearOptionsCache();
    }

    public function down(): void
    {
        // Retain the type because delivered notifications may reference it.
    }
};
