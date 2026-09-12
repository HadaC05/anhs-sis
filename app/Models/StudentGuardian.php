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
        'is_deceased',
    ];

    protected function casts(): array
    {
        return [
            'is_deceased' => 'boolean',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function attributesFromForm(array $validated, string $relationship): array
    {
        $isDeceased = (bool) ($validated[$relationship.'_is_deceased'] ?? false);

        return [
            'first_name' => $validated[$relationship.'_fname'] ?? null,
            'middle_name' => $validated[$relationship.'_mname'] ?? null,
            'last_name' => $validated[$relationship.'_lname'] ?? null,
            'suffix' => $validated[$relationship.'_suffix'] ?? null,
            'is_deceased' => $isDeceased,
            'contact_no' => $isDeceased ? null : ($validated[$relationship.'_contact_no'] ?? null),
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_ID');
    }
}
