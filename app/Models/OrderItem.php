<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'sku',
        'quantity_required',
        'quantity_confirmed',
        'is_confirmed',
        'packer_note',
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
        'quantity_required' => 'integer',
        'quantity_confirmed' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function remainingQuantity(): int
    {
        return max(0, $this->quantity_required - $this->quantity_confirmed);
    }

    public function markConfirmed(int $quantity): void
    {
        $newQuantity = min(
            $this->quantity_required,
            $this->quantity_confirmed + $quantity
        );

        $this->update([
            'quantity_confirmed' => $newQuantity,
            'is_confirmed' => $newQuantity >= $this->quantity_required,
        ]);
    }
}
