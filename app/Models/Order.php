<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * Status Constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PACKING = 'packing';
    public const STATUS_PACKED = 'packed';
    public const STATUS_READY_TO_SHIP = 'ready_to_ship';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_ISSUE = 'issue';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Priority Constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

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
        'pickup_deadline'     => 'datetime',
        'packing_started_at'  => 'datetime',
        'packed_at'           => 'datetime',
        'ready_to_ship_at'    => 'datetime',
        'shipped_at'          => 'datetime',
    ];

    /**
     * Relationships
     */

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PackingPhoto::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packer_id');
    }

    /**
     * Helpers
     */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPacking(): bool
    {
        return $this->status === self::STATUS_PACKING;
    }

    public function isPacked(): bool
    {
        return $this->status === self::STATUS_PACKED;
    }

    public function isReadyToShip(): bool
    {
        return $this->status === self::STATUS_READY_TO_SHIP;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function hasPackingPhoto(): bool
    {
        return $this->photos()->exists();
    }

    public function isFullyConfirmed(): bool
    {
        return $this->items()
            ->whereColumn('quantity_confirmed', '<', 'quantity_required')
            ->doesntExist();
    }

    /**
     * Status Logger
     */

    public function logStatus(
        ?string $fromStatus,
        string $toStatus,
        ?int $changedBy = null,
        ?string $note = null
    ): void {
        $this->statusLogs()->create([
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'changed_by'  => $changedBy,
            'note'        => $note,
        ]);
    }

    /**
     * Static Lists
     */

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PACKING => 'Packing',
            self::STATUS_PACKED => 'Packed',
            self::STATUS_READY_TO_SHIP => 'Ready To Ship',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_ISSUE => 'Issue',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }
}