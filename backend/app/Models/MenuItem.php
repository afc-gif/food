<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'barcode',
        'description',
        'price',
        'is_sold_out',
        'stock',
        'stock_unit',
        'image_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_sold_out' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (MenuItem $item) {
            if (empty($item->slug)) {
                $item->slug = Str::slug($item->name . '-' . Str::random(6));
            }

            if (empty($item->barcode)) {
                $item->barcode = static::generateBarcode();
            }
        });
    }

    public static function generateBarcode(): string
    {
        do {
            // 12-digit numeric code friendly to common barcode formats (e.g., Code 128)
            $barcode = (string) random_int(100_000_000_000, 999_999_999_999);
        } while (static::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function inventoryAdjustments()
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }
}
