<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'platform_product_id',
        'image',
        'packing_notes',
        'is_fragile',
    ];

    protected $casts = [
        'is_fragile' => 'boolean',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
