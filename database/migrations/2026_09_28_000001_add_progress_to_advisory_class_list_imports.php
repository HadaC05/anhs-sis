<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisory_class_list_imports', function (Blueprint $table): void {
            $table->longText('file_contents')->nullable()->change();
            $table->unsignedInteger('total_students')->nullable();
            $table->unsignedInteger('processed_students')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('advisory_class_list_imports', function (Blueprint $table): void {
            $table->dropColumn(['total_students', 'processed_students']);
        });
    }
};
