<?php

use App\Models\DocumentReturnReason;
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
        Schema::create('document_return_reasons', function (Blueprint $table) {
            $table->increments('reason_ID');
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        foreach (DocumentReturnReason::definitions() as $reason) {
            DB::table('document_return_reasons')->insert([
                'name' => $reason['name'],
                'description' => $reason['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('student_documents') && ! Schema::hasColumn('student_documents', 'return_reason_ID')) {
            Schema::table('student_documents', function (Blueprint $table) {
                $table->unsignedInteger('return_reason_ID')->nullable()->after('verified_by');
                $table->foreign('return_reason_ID')
                    ->references('reason_ID')
                    ->on('document_return_reasons')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('student_documents') && Schema::hasColumn('student_documents', 'return_reason_ID')) {
            Schema::table('student_documents', function (Blueprint $table) {
                $table->dropForeign(['return_reason_ID']);
                $table->dropColumn('return_reason_ID');
            });
        }

        Schema::dropIfExists('document_return_reasons');
    }
};
