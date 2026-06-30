<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'platform',
        'customer_name',
        'customer_phone',
        'shipping_address',
        'pickup_deadline',
        'priority',
        'status',
        'shipping_label',
        'created_by',
        'packer_id',
        'packing_started_at',
        'packed_at',
        'ready_to_ship_at',
        'shipped_at',
    ];

    protected $casts = [
        'pickup_deadline' => 'datetime',
        'packing_started_at' => 'datetime',
        'packed_at' => 'datetime',
        'ready_to_ship_at' => 'datetime',
        'shipped_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function photos()
    {
        return $this->hasMany(PackingPhoto::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function packer()
    {
        return $this->belongsTo(User::class, 'packer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isFullyConfirmed(): bool
    {
        return $this->items()
            ->whereColumn('quantity_confirmed', '<', 'quantity_required')
            ->doesntExist();
    }

    public function hasPackingPhoto(): bool
    {
        return $this->photos()->exists();
    }

    public function logStatus(?string $fromStatus, string $toStatus, ?int $changedBy = null, ?string $note = null): void
    {
        $this->statusLogs()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }
}
