<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookInventory extends Model
{
    use HasFactory;

    protected $table = 'book_inventory';
    protected $primaryKey = 'inventory_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'book_ID',
        'property_no',
        'condition',
        'status',
        'record_status',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(RefBook::class, 'book_ID', 'book_ID');
    }
}
