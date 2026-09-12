<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentReturnReason extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentReturnReasonFactory> */
    use HasFactory;

    protected $table = 'document_return_reasons';

    protected $primaryKey = 'reason_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @return list<array{name: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Unreadable or blurry copy', 'description' => 'The uploaded file cannot be read clearly.'],
            ['name' => 'Incomplete document', 'description' => 'One or more required pages or details are missing.'],
            ['name' => 'Wrong document type', 'description' => 'The file does not match the required document type.'],
            ['name' => 'Name or details do not match', 'description' => 'The learner details on the document do not match the enrollment record.'],
            ['name' => 'Expired document', 'description' => 'The document is no longer valid for enrollment.'],
            ['name' => 'Not a certified true copy', 'description' => 'A certified true copy or original scan is required.'],
            ['name' => 'Poor image quality', 'description' => 'The photo or scan is cropped, dark, or otherwise unusable.'],
            ['name' => 'Other', 'description' => 'Returned for another reason. Guidance will advise the student.'],
        ];
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'return_reason_ID', 'reason_ID');
    }
}
