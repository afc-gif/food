<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_url',
        'is_active',
        'sort_order',
    ];

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
}
