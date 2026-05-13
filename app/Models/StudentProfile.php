<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    protected $table = 'student_profiles';
    protected $primaryKey = 'profile_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'student_ID',
        'is_4ps',
        'four_ps_household_id',
        'is_ip',
        'ip_community',
        'has_disability',
        'disability_name',
    ];

    protected function casts(): array
    {
        return [
            'is_4ps' => 'boolean',
            'is_ip' => 'boolean',
            'has_disability' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_ID');
    }
}
