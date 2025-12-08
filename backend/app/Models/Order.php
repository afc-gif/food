<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'status',
        'channel',
        'created_by',
        'customer_name',
        'customer_phone',
        'subtotal',
        'tax',
        'discount',
        'total',
        'paid_at',
        'cancelled_at',
        'kitchen_status',
        'kitchen_eta_minutes',
        'kitchen_eta_at',
        'kitchen_sent_at',
        'kitchen_note',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'kitchen_eta_at' => 'datetime',
        'kitchen_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->code)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (static::where('code', $code)->exists());

                $order->code = $code;
            }
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
