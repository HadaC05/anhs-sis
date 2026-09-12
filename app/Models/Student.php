<?php

namespace App\Models;

use App\Models\Concerns\ChecksAccountStatus;
use App\Models\Concerns\HasTypedNotifications;
use App\Models\Concerns\ResolvesAccountRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Student extends Authenticatable
{
    use ChecksAccountStatus, HasFactory, HasTypedNotifications, Notifiable, ResolvesAccountRole {
        HasTypedNotifications::notifications insteadof Notifiable;
    }

    protected $fillable = [
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

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return strtoupper(substr($this->first_name ?: $this->username ?: 'S', 0, 1));
    }

    public function photoUrl(): ?string
    {
        $path = $this->photoDocument?->file_path;

        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
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

    /**
     * @return HasOne<StudentDocument, $this>
     */
    public function photoDocument(): HasOne
    {
        return $this->hasOne(StudentDocument::class, 'student_ID')
            ->where('doc_type', DocumentType::ID_PHOTO)
            ->orderByDesc('date_uploaded')
            ->orderByDesc('doc_ID');
    }

    /**
     * Determine whether all information required by the student profile form
     * has been supplied. This is intentionally kept on the model so the
     * portal gate and the dashboard prompt always use the same definition.
     */
    public function hasCompleteProfile(): bool
    {
        // Reload these records because this method is also called immediately
        // after a profile save, when an authenticated model may have cached an
        // earlier empty relation.
        $this->load(['profile', 'addresses', 'guardians']);

        foreach (['contact_no', 'sex', 'birthdate', 'birthplace', 'religion', 'mother_tongue'] as $field) {
            if (! filled($this->{$field})) {
                return false;
            }
        }

        if (! $this->profile) {
            return false;
        }

        if (($this->profile->is_4ps && ! filled($this->profile->four_ps_household_id))
            || ($this->profile->is_ip && ! filled($this->profile->ip_community))
            || ($this->profile->has_disability && ! filled($this->profile->disability_name))) {
            return false;
        }

        foreach (['current', 'permanent'] as $type) {
            $address = $this->addresses->firstWhere('address_type', $type);

            if (! $address || ! filled($address->barangay) || ! filled($address->municipality)
                || ! filled($address->province) || ! filled($address->zip_code)) {
                return false;
            }
        }

        foreach (['father', 'mother'] as $relationship) {
            $guardian = $this->guardians->firstWhere('relationship', $relationship);

            if (! $guardian || ! filled($guardian->first_name) || ! filled($guardian->last_name)) {
                return false;
            }
        }

        return true;
    }
}
