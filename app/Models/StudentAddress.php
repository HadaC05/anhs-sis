<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAddress extends Model
{
    use HasFactory;

    protected $table = 'student_addresses';
    protected $primaryKey = 'address_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'student_ID',
        'address_type',
        'house_no',
        'street_name',
        'barangay',
        'municipality',
        'province',
        'country',
        'zip_code',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_ID');
    }
}
