<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementReason extends Model
{
    use HasFactory;

    protected $table = 'movement_reasons';
    protected $primaryKey = 'reason_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
    ];

    public function studentMovements(): HasMany
    {
        return $this->hasMany(StudentMovement::class, 'reason_ID', 'reason_ID');
    }
}
