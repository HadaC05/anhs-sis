<?php

use App\Models\LearnerType;
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
        Schema::create('learner_types', function (Blueprint $table) {
            $table->increments('learner_type_ID');
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });

        $now = now();

        foreach (LearnerType::definitions() as $type) {
            DB::table('learner_types')->insert([
                'slug' => $type['slug'],
                'name' => $type['name'],
                'sort_order' => $type['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        LearnerType::clearOptionsCache();

        $this->convertLearnerTypeColumn();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'learner_type_ID')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropForeign('enrollments_learner_type_id_foreign');
            });

            Schema::table('enrollments', function (Blueprint $table) {
                $table->string('learner_type')->default('regular')->after('learner_type_ID');
            });

            $typeSlugs = DB::table('learner_types')->pluck('slug', 'learner_type_ID');

            foreach ($typeSlugs as $id => $slug) {
                DB::table('enrollments')
                    ->where('learner_type_ID', $id)
                    ->update(['learner_type' => $slug]);
            }

            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('learner_type_ID');
            });

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE enrollments MODIFY learner_type ENUM('regular','transferee','balik_aral','returnee') NOT NULL");
            }
        }

        Schema::dropIfExists('learner_types');
        LearnerType::clearOptionsCache();
    }

    private function convertLearnerTypeColumn(): void
    {
        if (! Schema::hasTable('enrollments') || Schema::hasColumn('enrollments', 'learner_type_ID')) {
            return;
        }

        if (! Schema::hasColumn('enrollments', 'learner_type')) {
            return;
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('learner_type_ID')->nullable()->after('learner_type');
        });

        $typeIds = DB::table('learner_types')->pluck('learner_type_ID', 'slug');
        $regularId = $typeIds[LearnerType::REGULAR] ?? null;
        $balikAralId = $typeIds[LearnerType::BALIK_ARAL] ?? null;

        foreach ($typeIds as $slug => $id) {
            DB::table('enrollments')->where('learner_type', $slug)->update([
                'learner_type_ID' => $id,
            ]);
        }

        if ($balikAralId !== null) {
            DB::table('enrollments')->where('learner_type', LearnerType::RETURNEE_ALIAS)->update([
                'learner_type_ID' => $balikAralId,
            ]);
        }

        DB::table('enrollments')->whereNull('learner_type_ID')->update([
            'learner_type_ID' => $regularId,
        ]);

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('learner_type');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE enrollments MODIFY learner_type_ID INT UNSIGNED NOT NULL');
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('learner_type_ID', 'enrollments_learner_type_id_foreign')
                ->references('learner_type_ID')
                ->on('learner_types')
                ->restrictOnDelete();
        });
    }
};
