<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('audit_id');
            $table->dateTime('timestamp')->index();
            // Snapshot identifiers: entries survive account changes and deletion.
            $table->string('user_id')->nullable()->index();
            $table->string('user_name');
            $table->string('role', 80)->index();
            $table->string('action', 80)->index();
            $table->string('module', 100)->index();
            $table->longText('reference')->nullable();
            $table->longText('description');
            $table->string('status', 30)->index();
        });
        Schema::table('advisory_class_list_imports', function (Blueprint $table): void {
            $table->json('audit_actor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('advisory_class_list_imports', function (Blueprint $table): void {
            $table->dropColumn('audit_actor');
        });
        Schema::dropIfExists('audit_logs');
    }
};
