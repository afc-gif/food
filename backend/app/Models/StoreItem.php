<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'store_category_id',
        'quantity',
        'unit',
        'low_stock_threshold',
        'supplier_notes',
    ];

    protected $casts = [
        'quantity'            => 'decimal:2',
        'low_stock_threshold' => 'decimal:2',
    ];

    protected $appends = ['status'];

    /**
     * "out_of_stock" | "low_stock" | "in_stock"
     */
    public function getStatusAttribute(): string
    {
        if ((float) $this->quantity <= 0) {
            return 'out_of_stock';
        }

        if ((float) $this->quantity <= (float) $this->low_stock_threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function category()
    {
        return $this->belongsTo(StoreCategory::class, 'store_category_id');
    }

    public function adjustments()
    {
        return $this->hasMany(StoreAdjustment::class);
    }
}
