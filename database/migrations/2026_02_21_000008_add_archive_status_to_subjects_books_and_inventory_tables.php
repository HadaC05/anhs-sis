<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'status')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->enum('status', ['active', 'archived'])->default('active')->after('type');
            });
        }

        if (Schema::hasTable('ref_books') && ! Schema::hasColumn('ref_books', 'status')) {
            Schema::table('ref_books', function (Blueprint $table) {
                $table->enum('status', ['active', 'archived'])->default('active')->after('grade_level');
            });
        }

        if (Schema::hasTable('book_inventory') && ! Schema::hasColumn('book_inventory', 'record_status')) {
            Schema::table('book_inventory', function (Blueprint $table) {
                $table->enum('record_status', ['active', 'archived'])->default('active')->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('book_inventory') && Schema::hasColumn('book_inventory', 'record_status')) {
            Schema::table('book_inventory', function (Blueprint $table) {
                $table->dropColumn('record_status');
            });
        }

        if (Schema::hasTable('ref_books') && Schema::hasColumn('ref_books', 'status')) {
            Schema::table('ref_books', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'status')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
