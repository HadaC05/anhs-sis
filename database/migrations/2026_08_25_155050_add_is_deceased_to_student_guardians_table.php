<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_guardians', function (Blueprint $table) {
            $table->boolean('is_deceased')->default(false)->after('contact_no');
        });
    }

    public function down(): void
    {
        Schema::table('student_guardians', function (Blueprint $table) {
            $table->dropColumn('is_deceased');
        });
    }
};
