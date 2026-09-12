<?php

use App\Models\PlacementStatus;
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
        Schema::create('placement_statuses', function (Blueprint $table) {
            $table->increments('placement_status_ID');
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });

        $now = now();

        foreach (PlacementStatus::definitions() as $status) {
            DB::table('placement_statuses')->insert([
                'slug' => $status['slug'],
                'name' => $status['name'],
                'sort_order' => $status['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        PlacementStatus::clearOptionsCache();

        $this->convertPlacementTestRecommendedColumn();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'placement_status_ID')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropForeign('enrollments_placement_status_id_foreign');
            });

            Schema::table('enrollments', function (Blueprint $table) {
                $table->boolean('placement_test_recommended')->default(false)->after('placement_status_ID');
            });

            $recommendedId = DB::table('placement_statuses')
                ->where('slug', PlacementStatus::RECOMMENDED)
                ->value('placement_status_ID');

            if ($recommendedId !== null) {
                DB::table('enrollments')
                    ->where('placement_status_ID', $recommendedId)
                    ->update(['placement_test_recommended' => true]);
            }

            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('placement_status_ID');
            });
        }

        Schema::dropIfExists('placement_statuses');
        PlacementStatus::clearOptionsCache();
    }

    private function convertPlacementTestRecommendedColumn(): void
    {
        if (! Schema::hasTable('enrollments') || Schema::hasColumn('enrollments', 'placement_status_ID')) {
            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'enrollment_status_ID')) {
                $table->unsignedInteger('placement_status_ID')->nullable()->after('enrollment_status_ID');
            } else {
                $table->unsignedInteger('placement_status_ID')->nullable();
            }
        });

        $statusIds = DB::table('placement_statuses')->pluck('placement_status_ID', 'slug');
        $pendingId = $statusIds[PlacementStatus::PENDING] ?? null;
        $recommendedId = $statusIds[PlacementStatus::RECOMMENDED] ?? null;

        if (Schema::hasColumn('enrollments', 'placement_test_recommended')) {
            if ($recommendedId !== null) {
                DB::table('enrollments')
                    ->where('placement_test_recommended', true)
                    ->update(['placement_status_ID' => $recommendedId]);
            }

            if ($pendingId !== null) {
                DB::table('enrollments')
                    ->where(function ($query) {
                        $query->where('placement_test_recommended', false)
                            ->orWhereNull('placement_status_ID');
                    })
                    ->update(['placement_status_ID' => $pendingId]);
            }

            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('placement_test_recommended');
            });
        } elseif ($pendingId !== null) {
            DB::table('enrollments')->whereNull('placement_status_ID')->update([
                'placement_status_ID' => $pendingId,
            ]);
        }

        if ($pendingId !== null && Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE enrollments MODIFY placement_status_ID INT UNSIGNED NOT NULL');
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('placement_status_ID', 'enrollments_placement_status_id_foreign')
                ->references('placement_status_ID')
                ->on('placement_statuses')
                ->restrictOnDelete();
        });
    }
};
