<?php

use App\Models\DocumentStatus;
use App\Models\DocumentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createDocumentTypesTable();
        $this->createDocumentStatusesTable();
        $this->convertStudentDocumentColumns();
        $this->migrateProfilePhotos();
        $this->dropStudentPhotoPath();
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'photo_path')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('photo_path')->nullable()->after('email');
            });
        }

        if (Schema::hasTable('student_documents') && Schema::hasColumn('student_documents', 'document_type_ID')) {
            Schema::table('student_documents', function (Blueprint $table): void {
                $table->dropForeign('student_documents_document_type_id_foreign');
                $table->dropForeign('student_documents_document_status_id_foreign');
            });

            Schema::table('student_documents', function (Blueprint $table): void {
                $table->string('doc_type')->nullable()->after('student_ID');
                $table->string('status')->default('pending')->after('file_path');
            });

            $typeSlugs = DB::table('document_types')->pluck('slug', 'document_type_ID');
            $statusSlugs = DB::table('document_statuses')->pluck('slug', 'document_status_ID');

            foreach ($typeSlugs as $id => $slug) {
                DB::table('student_documents')->where('document_type_ID', $id)->update(['doc_type' => $slug]);
            }

            foreach ($statusSlugs as $id => $slug) {
                DB::table('student_documents')->where('document_status_ID', $id)->update(['status' => $slug]);
            }

            Schema::table('student_documents', function (Blueprint $table): void {
                $table->dropColumn(['document_type_ID', 'document_status_ID']);
            });
        }

        Schema::dropIfExists('document_statuses');
        Schema::dropIfExists('document_types');
        DocumentType::clearOptionsCache();
        DocumentStatus::clearOptionsCache();
    }

    private function createDocumentTypesTable(): void
    {
        if (! Schema::hasTable('document_types')) {
            Schema::create('document_types', function (Blueprint $table): void {
                $table->increments('document_type_ID');
                $table->string('slug')->unique();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_required')->default(false);
                $table->unsignedTinyInteger('sort_order');
                $table->timestamps();
            });
        }

        $now = now();

        foreach (DocumentType::definitions() as $type) {
            DB::table('document_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'is_required' => $type['is_required'],
                    'sort_order' => $type['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DocumentType::clearOptionsCache();
    }

    private function createDocumentStatusesTable(): void
    {
        if (! Schema::hasTable('document_statuses')) {
            Schema::create('document_statuses', function (Blueprint $table): void {
                $table->increments('document_status_ID');
                $table->string('slug')->unique();
                $table->string('name')->unique();
                $table->unsignedTinyInteger('sort_order');
                $table->timestamps();
            });
        }

        $now = now();

        foreach (DocumentStatus::definitions() as $status) {
            DB::table('document_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        DocumentStatus::clearOptionsCache();
    }

    private function convertStudentDocumentColumns(): void
    {
        if (! Schema::hasTable('student_documents') || Schema::hasColumn('student_documents', 'document_type_ID')) {
            return;
        }

        Schema::table('student_documents', function (Blueprint $table): void {
            $table->unsignedInteger('document_type_ID')->nullable()->after('student_ID');
            $table->unsignedInteger('document_status_ID')->nullable()->after('file_path');
        });

        $typeIds = DB::table('document_types')->pluck('document_type_ID', 'slug');
        $statusIds = DB::table('document_statuses')->pluck('document_status_ID', 'slug');
        $pendingId = $statusIds[DocumentStatus::PENDING] ?? null;
        $returnedId = $statusIds[DocumentStatus::RETURNED] ?? null;
        $fallbackTypeId = $typeIds[DocumentType::GOOD_MORAL] ?? $typeIds->first();

        foreach ($typeIds as $slug => $id) {
            DB::table('student_documents')->where('doc_type', $slug)->update([
                'document_type_ID' => $id,
            ]);
        }

        DB::table('student_documents')->whereNull('document_type_ID')->update([
            'document_type_ID' => $fallbackTypeId,
        ]);

        foreach ($statusIds as $slug => $id) {
            DB::table('student_documents')->where('status', $slug)->update([
                'document_status_ID' => $id,
            ]);
        }

        DB::table('student_documents')->where('status', 'rejected')->update([
            'document_status_ID' => $returnedId,
        ]);

        DB::table('student_documents')->whereNull('document_status_ID')->update([
            'document_status_ID' => $pendingId,
        ]);

        Schema::table('student_documents', function (Blueprint $table): void {
            $table->dropColumn(['doc_type', 'status']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE student_documents MODIFY document_type_ID INT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE student_documents MODIFY document_status_ID INT UNSIGNED NOT NULL');
        }

        Schema::table('student_documents', function (Blueprint $table): void {
            $table->foreign('document_type_ID', 'student_documents_document_type_id_foreign')
                ->references('document_type_ID')
                ->on('document_types')
                ->restrictOnDelete();
            $table->foreign('document_status_ID', 'student_documents_document_status_id_foreign')
                ->references('document_status_ID')
                ->on('document_statuses')
                ->restrictOnDelete();
        });
    }

    private function migrateProfilePhotos(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'photo_path')) {
            return;
        }

        $typeId = DocumentType::idFor(DocumentType::ID_PHOTO);
        $pendingId = DocumentStatus::idFor(DocumentStatus::PENDING);

        if ($typeId === null || $pendingId === null) {
            return;
        }

        $now = now();

        $students = DB::table('students')
            ->whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->get(['id', 'photo_path']);

        foreach ($students as $student) {
            $exists = DB::table('student_documents')
                ->where('student_ID', $student->id)
                ->where('document_type_ID', $typeId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('student_documents')->insert([
                'student_ID' => $student->id,
                'document_type_ID' => $typeId,
                'file_path' => $student->photo_path,
                'document_status_ID' => $pendingId,
                'date_uploaded' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function dropStudentPhotoPath(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'photo_path')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('photo_path');
            });
        }
    }
};
