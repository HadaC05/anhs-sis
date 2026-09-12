<?php

namespace App\Models;

use App\Models\Builders\StudentDocumentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class StudentDocument extends Model
{
    use HasFactory;

    protected $table = 'student_documents';

    protected $primaryKey = 'doc_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'student_ID',
        'doc_type',
        'document_type_ID',
        'file_path',
        'status',
        'document_status_ID',
        'date_uploaded',
        'date_verified',
        'verified_by',
        'return_reason_ID',
    ];

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    public function newEloquentBuilder($query): StudentDocumentBuilder
    {
        return new StudentDocumentBuilder($query);
    }

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            if (empty($document->document_status_ID) && $document->status === '') {
                $document->status = DocumentStatus::PENDING;
            }
        });
    }

    /**
     * @return array<string, array{title: string, desc: string, required: bool}>
     */
    public static function typeDefinitions(): array
    {
        return DocumentType::typeDefinitions(enrollmentOnly: true);
    }

    /**
     * @return list<string>
     */
    public static function typeKeys(): array
    {
        return array_keys(self::typeDefinitions());
    }

    /**
     * @return list<string>
     */
    public static function requiredTypes(): array
    {
        return DocumentType::requiredSlugs();
    }

    public function getRouteKeyName(): string
    {
        return 'doc_ID';
    }

    protected function casts(): array
    {
        return [
            'date_uploaded' => 'datetime',
            'date_verified' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_ID');
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_ID', 'document_type_ID');
    }

    /**
     * @return BelongsTo<DocumentStatus, $this>
     */
    public function documentStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class, 'document_status_ID', 'document_status_ID');
    }

    /**
     * @return BelongsTo<DocumentReturnReason, $this>
     */
    public function returnReason(): BelongsTo
    {
        return $this->belongsTo(DocumentReturnReason::class, 'return_reason_ID', 'reason_ID');
    }

    public function getDocTypeAttribute(): string
    {
        if (array_key_exists('doc_type', $this->attributes) && ! array_key_exists('document_type_ID', $this->attributes)) {
            return (string) $this->attributes['doc_type'];
        }

        if ($this->relationLoaded('documentType')) {
            return (string) ($this->getRelation('documentType')?->slug ?? '');
        }

        $typeId = $this->attributes['document_type_ID'] ?? null;

        return $typeId ? (string) (DocumentType::slugFor((int) $typeId) ?? '') : '';
    }

    public function setDocTypeAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['document_type_ID'] = null;

            return;
        }

        if (! Schema::hasTable('document_types')) {
            $this->attributes['doc_type'] = $value;

            return;
        }

        $typeId = DocumentType::idFor($value);

        if ($typeId === null) {
            throw new InvalidArgumentException("Unknown document type [{$value}].");
        }

        $this->attributes['document_type_ID'] = $typeId;
    }

    public function getStatusAttribute(): string
    {
        if (array_key_exists('status', $this->attributes) && ! array_key_exists('document_status_ID', $this->attributes)) {
            return (string) $this->attributes['status'];
        }

        if ($this->relationLoaded('documentStatus')) {
            return (string) ($this->getRelation('documentStatus')?->slug ?? '');
        }

        $statusId = $this->attributes['document_status_ID'] ?? null;

        return $statusId ? (string) (DocumentStatus::slugFor((int) $statusId) ?? '') : '';
    }

    public function setStatusAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['document_status_ID'] = DocumentStatus::idFor(DocumentStatus::PENDING);

            return;
        }

        if (! Schema::hasTable('document_statuses')) {
            $this->attributes['status'] = $value;

            return;
        }

        $statusId = DocumentStatus::idFor($value);

        if ($statusId === null) {
            throw new InvalidArgumentException("Unknown document status [{$value}].");
        }

        $this->attributes['document_status_ID'] = $statusId;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->relationLoaded('documentStatus')) {
            return $this->getRelation('documentStatus')?->name ?: DocumentStatus::nameFor($this->status);
        }

        return DocumentStatus::nameFor($this->status);
    }

    public function isVerified(): bool
    {
        return $this->status === DocumentStatus::VERIFIED;
    }

    public function isReturned(): bool
    {
        return $this->status === DocumentStatus::RETURNED;
    }

    public function isRejected(): bool
    {
        return $this->isReturned();
    }
}
