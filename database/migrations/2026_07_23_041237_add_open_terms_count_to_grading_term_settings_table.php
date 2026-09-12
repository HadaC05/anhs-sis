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

        if (! Schema::hasColumn('grading_term_settings', 'open_terms_count')) {
            Schema::table('grading_term_settings', function (Blueprint $table) {
                $table->unsignedSmallInteger('open_terms_count')->default(1)->after('max_terms');
            });
        }

        DB::table('grading_term_settings')
            ->where('id', 1)
            ->update(['open_terms_count' => 1]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('grading_term_settings')) {
            return;
        }

        if (Schema::hasColumn('grading_term_settings', 'open_terms_count')) {
            Schema::table('grading_term_settings', function (Blueprint $table) {
                $table->dropColumn('open_terms_count');
            });
        }
    }
};
