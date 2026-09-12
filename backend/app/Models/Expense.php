<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'amount',
        'quantity',
        'unit',
        'price_per_unit',
        'category',
        'buyer_name',
        'expense_date',
        'logged_by',
        'created_by',
        'note',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'quantity'       => 'decimal:3',
        'price_per_unit' => 'decimal:2',
        'expense_date'   => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
