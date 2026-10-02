<?php

use App\Models\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notification_types')->updateOrInsert(
            ['slug' => NotificationType::ACADEMIC_SUPPORT],
            ['name' => 'Academic support reminder', 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
        );
        NotificationType::clearOptionsCache();
    }

    public function down(): void
    {
        DB::table('notification_types')->where('slug', NotificationType::ACADEMIC_SUPPORT)->delete();
        NotificationType::clearOptionsCache();
    }
};
