<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grading_term_settings')) {
            return;
        }

        if (! Schema::hasColumn('grading_term_settings', 'shs_semester')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->string('shs_semester', 20)->default('first')->after('open_terms_count');
            });
        }

        if (! Schema::hasColumn('grading_term_settings', 'shs_quarter')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('shs_quarter')->default(1)->after('shs_semester');
            });
        }

        DB::table('grading_term_settings')
            ->where('id', 1)
            ->update([
                'shs_semester' => 'first',
                'shs_quarter' => 1,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('grading_term_settings')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('grading_term_settings', 'shs_quarter') ? 'shs_quarter' : null,
            Schema::hasColumn('grading_term_settings', 'shs_semester') ? 'shs_semester' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('grading_term_settings', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
