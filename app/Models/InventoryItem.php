<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id', 'fulfillment_location_id', 'sku', 'name',
        'quantity_on_hand', 'quantity_reserved', 'reorder_point', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'reorder_point' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function fulfillmentLocation(): BelongsTo
    {
        return $this->belongsTo(FulfillmentLocation::class);
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }
}
