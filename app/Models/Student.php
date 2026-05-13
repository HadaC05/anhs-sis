<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'username',
        'password',
        'change_password',
        'password_changed_at',
        'lrn',
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
        'activated_by',
        'activated_at',
        'rejection_reason_id',
        'submitted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'password' => 'hashed',
            'change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'activated_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return strtoupper(substr($this->first_name ?: $this->username ?: 'S', 0, 1));
    }

    public function getAuthIdentifier()
    {
        return 'student:'.$this->getKey();
    }

    public function getStudentAttribute(): self
    {
        return $this;
    }

    public function getApplicationAttribute(): self
    {
        return $this;
    }

    public function student(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'id');
    }

    public function application(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'id');
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'activated_by', 'staff_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'student_ID');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'student_ID');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class, 'student_ID');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(StudentAddress::class, 'student_ID');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'student_ID');
    }
}
