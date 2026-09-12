<?php

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
        if (Schema::hasColumn('students', 'role_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('role_id');
            });
        }

        DB::table('roles')->where('role_name', 'student')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('roles')->where('role_name', 'student')->doesntExist()) {
            DB::table('roles')->insert([
                'role_name' => 'student',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasColumn('students', 'role_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            });
        }
    }
};
