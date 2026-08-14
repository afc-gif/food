<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_item_id',
        'quantity_change',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2',
    ];

    public function storeItem()
    {
        return $this->belongsTo(StoreItem::class);
    }
}
