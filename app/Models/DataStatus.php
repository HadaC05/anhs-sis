<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataStatus extends Model
{
    use HasFactory;

    protected $primaryKey = 'data_status_ID';

    protected $fillable = ['key', 'label', 'sort_order'];

    public function curricula(): HasMany
    {
        return $this->hasMany(Curricula::class, 'data_status_ID', 'data_status_ID');
    }
}
