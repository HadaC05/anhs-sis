<?php

use App\Models\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (NotificationType::definitions() as $type) {
            $payload = [
                'name' => $type['name'],
                'sort_order' => $type['sort_order'],
                'updated_at' => now(),
            ];

            if (DB::table('notification_types')->where('slug', $type['slug'])->exists()) {
                DB::table('notification_types')->where('slug', $type['slug'])->update($payload);
            } else {
                DB::table('notification_types')->insert($payload + [
                    'slug' => $type['slug'],
                    'created_at' => now(),
                ]);
            }
        }

        NotificationType::clearOptionsCache();
    }

    public function down(): void
    {
        DB::table('notification_types')->whereIn('slug', [
            NotificationType::GRADING_TERM_OPENED,
            NotificationType::GRADES_UNLOCKED,
            NotificationType::DOCUMENT_STATUS_UPDATED,
        ])->delete();

        NotificationType::clearOptionsCache();
    }
};
