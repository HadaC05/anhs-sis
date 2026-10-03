<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specializations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('staffs', function (Blueprint $table) {
            $table->foreignId('specialization_id')->nullable()->constrained()->restrictOnDelete();
        });

        $names = collect([
            'Mathematics', 'Science', 'English', 'Filipino', 'Araling Panlipunan',
            'Social Science', 'MAPEH', 'Technology and Livelihood Education',
            'Values Education', 'Research', 'General',
        ])->merge(DB::table('staffs')->whereNotNull('major_specialization')->pluck('major_specialization'));

        foreach ($names->map(fn ($name) => trim($name))->filter()->unique() as $name) {
            DB::table('specializations')->insertOrIgnore([
                'name' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('staffs')->whereNotNull('major_specialization')->orderBy('staff_id')
            ->each(function ($staff) {
                $id = DB::table('specializations')->where('name', trim($staff->major_specialization))->value('id');
                DB::table('staffs')->where('staff_id', $staff->staff_id)->update(['specialization_id' => $id]);
            });
    }

    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialization_id');
        });
        Schema::dropIfExists('specializations');
    }
};
