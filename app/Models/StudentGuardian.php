<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuardian extends Model
{
    use HasFactory;

    protected $table = 'student_guardians';
    protected $primaryKey = 'guardian_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'student_ID',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'relationship',
        'contact_no',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_ID');
    }
}
