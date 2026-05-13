<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'file_path',
        'status',
        'date_uploaded',
        'date_verified',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'date_uploaded' => 'datetime',
            'date_verified' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_ID');
    }
}
