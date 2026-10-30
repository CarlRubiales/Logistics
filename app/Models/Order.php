<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id', 'external_id', 'order_number', 'customer_name',
        'customer_email', 'shipping_address', 'status', 'total',
        'ordered_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'metadata' => 'array',
            'ordered_at' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
