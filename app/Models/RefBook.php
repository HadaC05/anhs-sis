<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefBook extends Model
{
    use HasFactory;

    protected $table = 'ref_books';
    protected $primaryKey = 'book_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'subject_ID',
        'book_code',
        'title',
        'author',
        'grade_level',
        'status',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_ID', 'subject_ID');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(BookInventory::class, 'book_ID', 'book_ID');
    }
}
