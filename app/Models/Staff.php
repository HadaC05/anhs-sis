<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class Staff extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $table = 'staffs';
    protected $primaryKey = 'staff_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'role_id',
        'username',
        'password',
        'change_password',
        'password_changed_at',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'gender',
        'birthdate',
        'email',
        'mobile_no',
        'employee_no',
        'plantilla_item_no',
        'appointment_status',
        'fund_source',
        'degree_earned',
        'major_specialization',
        'teaching_minutes',
        'status',
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

    public function getIdAttribute(): int
    {
        return $this->staff_id;
    }

    public function getAuthIdentifier()
    {
        return 'staff:'.$this->staff_id;
    }

    public function initials(): string
    {
        return strtoupper(substr($this->first_name ?: $this->username ?: 'U', 0, 1));
    }

    public function getStaffAttribute(): self
    {
        return $this;
    }

    public function staff(): HasOne
    {
        return $this->hasOne(self::class, 'staff_id', 'staff_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'staff_ID', 'staff_id');
    }
}
