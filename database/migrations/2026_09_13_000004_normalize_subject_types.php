<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_types', function (Blueprint $table): void {
            $table->increments('subject_type_ID');
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('subject_types')->insert([
            ['key' => 'core', 'label' => 'Core', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'applied', 'label' => 'Applied', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'specialized', 'label' => 'Specialized', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('subjects', function (Blueprint $table): void {
            $table->unsignedInteger('subject_type_ID')->nullable()->after('title');
        });

        $typeIds = DB::table('subject_types')->pluck('subject_type_ID', 'key');
        DB::table('subjects')->orderBy('subject_ID')->each(function (object $subject) use ($typeIds): void {
            DB::table('subjects')->where('subject_ID', $subject->subject_ID)->update([
                'subject_type_ID' => $typeIds[$subject->type] ?? $typeIds['core'],
            ]);
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->foreign('subject_type_ID')->references('subject_type_ID')->on('subject_types')->restrictOnDelete();
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->enum('type', ['core', 'applied', 'specialized'])->nullable()->after('title');
        });

        DB::table('subjects')->orderBy('subject_ID')->each(function (object $subject): void {
            $key = DB::table('subject_types')->where('subject_type_ID', $subject->subject_type_ID)->value('key');
            DB::table('subjects')->where('subject_ID', $subject->subject_ID)->update(['type' => $key ?? 'core']);
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropForeign(['subject_type_ID']);
            $table->dropColumn('subject_type_ID');
        });

        Schema::dropIfExists('subject_types');
    }
};
