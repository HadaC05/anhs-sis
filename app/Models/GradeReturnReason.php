<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeReturnReason extends Model
{
    use HasFactory;

    protected $table = 'grade_return_reasons';

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
            ['name' => 'Incomplete or missing grades', 'description' => 'One or more learner grades are incomplete or missing.'],
            ['name' => 'Incorrect grade entry', 'description' => 'Please review the entered grade values for accuracy.'],
            ['name' => 'Incorrect remarks', 'description' => 'Please correct the grade remarks before resubmitting.'],
            ['name' => 'Supporting records need review', 'description' => 'Please verify the grades against the supporting class records.'],
            ['name' => 'Other', 'description' => 'Please coordinate with the registrar for the required correction.'],
        ];
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'grade_return_reason_ID', 'reason_ID');
    }
}
