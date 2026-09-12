<?php

use App\Models\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->increments('notification_type_ID');
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });

        $now = now();

        foreach (NotificationType::definitions() as $type) {
            DB::table('notification_types')->insert([
                'slug' => $type['slug'],
                'name' => $type['name'],
                'sort_order' => $type['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        NotificationType::clearOptionsCache();

        if (Schema::hasTable('notifications') && ! Schema::hasColumn('notifications', 'notification_type_ID')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->unsignedInteger('notification_type_ID')->nullable()->after('type');
            });

            $this->backfillNotificationTypes();

            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('notification_type_ID', 'notifications_notification_type_id_foreign')
                    ->references('notification_type_ID')
                    ->on('notification_types')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'notification_type_ID')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign('notifications_notification_type_id_foreign');
            });

            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('notification_type_ID');
            });
        }

        Schema::dropIfExists('notification_types');
        NotificationType::clearOptionsCache();
    }

    private function backfillNotificationTypes(): void
    {
        $typeIds = DB::table('notification_types')->pluck('notification_type_ID', 'slug');

        $legacySlugs = [
            'enrollment' => NotificationType::ENROLLMENT_STATUS,
            'placement' => NotificationType::PLACEMENT_STATUS,
            'grades_approved' => NotificationType::GRADES_APPROVED,
            'grades_released' => NotificationType::GRADES_RELEASED,
        ];

        foreach (DB::table('notifications')->orderBy('id')->cursor() as $notification) {
            $data = json_decode((string) $notification->data, true);

            if (! is_array($data)) {
                $data = [];
            }

            $slug = $data['notification_type'] ?? $legacySlugs[$data['type'] ?? ''] ?? null;

            if (($data['type'] ?? null) === 'placement' && str_contains((string) ($data['title'] ?? ''), 'recommended')) {
                $slug = NotificationType::PLACEMENT_TEST_RECOMMENDED;
            }

            $typeId = $slug ? ($typeIds[$slug] ?? null) : null;

            if ($typeId === null) {
                continue;
            }

            $data['notification_type_ID'] = (int) $typeId;
            unset($data['type']);

            DB::table('notifications')->where('id', $notification->id)->update([
                'notification_type_ID' => $typeId,
                'data' => json_encode($data),
            ]);
        }
    }
};
