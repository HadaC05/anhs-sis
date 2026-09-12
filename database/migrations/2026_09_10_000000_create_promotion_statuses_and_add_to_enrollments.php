<?php

use App\Models\PromotionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_statuses', function (Blueprint $table): void {
            $table->increments('promotion_status_ID');
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });

        $now = now();
        foreach (PromotionStatus::definitions() as $status) {
            DB::table('promotion_statuses')->insert(array_merge($status, ['created_at' => $now, 'updated_at' => $now]));
        }

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->unsignedInteger('promotion_status_ID')->nullable()->after('placement_status_ID');
            $table->foreign('promotion_status_ID')->references('promotion_status_ID')->on('promotion_statuses')->restrictOnDelete();
        });

        DB::table('enrollments')->update([
            'promotion_status_ID' => DB::table('promotion_statuses')->where('slug', PromotionStatus::PENDING)->value('promotion_status_ID'),
        ]);
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropForeign(['promotion_status_ID']);
            $table->dropColumn('promotion_status_ID');
        });
        Schema::dropIfExists('promotion_statuses');
    }
};
