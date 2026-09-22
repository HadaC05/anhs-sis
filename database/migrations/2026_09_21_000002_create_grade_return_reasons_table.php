<?php

use App\Models\GradeReturnReason;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_return_reasons', function (Blueprint $table): void {
            $table->increments('reason_ID');
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        foreach (GradeReturnReason::definitions() as $reason) {
            DB::table('grade_return_reasons')->insert([
                'name' => $reason['name'],
                'description' => $reason['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('student_subject_grades', function (Blueprint $table): void {
            $table->unsignedInteger('grade_return_reason_ID')->nullable()->after('reviewed_at');
            $table->foreign('grade_return_reason_ID')
                ->references('reason_ID')
                ->on('grade_return_reasons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_subject_grades', function (Blueprint $table): void {
            $table->dropForeign(['grade_return_reason_ID']);
            $table->dropColumn('grade_return_reason_ID');
        });

        Schema::dropIfExists('grade_return_reasons');
    }
};
