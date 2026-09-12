<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentApplication extends Model
{
    use HasFactory;

    public const EARLIEST_BIRTHDATE = '1950-01-01';

    public const LATEST_BIRTHDATE = '2016-12-31';

    protected $table = 'students';

    protected $fillable = [
        'lrn',
        'username',
        'password',
        'change_password',
        'password_changed_at',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'contact_no',
        'sex',
        'birthdate',
        'birthplace',
        'mother_tongue',
        'religion',
        'status',
        'submitted_at',
        'activated_by',
        'activated_at',
        'rejection_reason_id',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'submitted_at' => 'datetime',
            'activated_at' => 'datetime',
            'change_password' => 'boolean',
            'password_changed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'activated_by', 'staff_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class, 'id', 'id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_ID', 'id');
    }

    /**
     * @return list<string>
     */
    public static function suffixOptions(): array
    {
        return ['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V'];
    }
}
