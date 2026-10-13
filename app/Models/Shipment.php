<?php

namespace App\Models;

use App\Services\ShipmentStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Shipment extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'pending', 'carrier' => 'Lumora Express'];

    public array $trackingContext = [];

    protected $fillable = [
        'order_id', 'fulfillment_location_id', 'external_id', 'tracking_number',
        'carrier', 'service_level', 'status', 'destination_city',
        'destination_state', 'destination_country', 'weight', 'label_url',
        'exception_type', 'exception_note', 'shipped_at', 'delivered_at', 'metadata',
    ];

    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    protected static function booted(): void
    {
        static::updating(function (Shipment $shipment) {
            if (! $shipment->isDirty('status')) {
                return;
            }
            if (! ShipmentStatusService::allowed($shipment, $shipment->getOriginal('status'), $shipment->status)) {
                throw ValidationException::withMessages(['status' => 'Invalid backward shipment transition.']);
            }
            if (in_array($shipment->status, ['in_transit', 'out_for_delivery'], true)) {
                $shipment->shipped_at ??= now();
            }
            if ($shipment->status === 'delivered') {
                $shipment->delivered_at ??= now();
            }
        });
        static::updated(function (Shipment $shipment) {
            if (! $shipment->wasChanged('status')) {
                return;
            }
            $context = $shipment->trackingContext;
            $shipment->trackingEvents()->create([
                'status' => $shipment->status,
                'label' => $context['label'] ?? ShipmentStatusService::label($shipment->status),
                'description' => $context['description'] ?? ShipmentStatusService::description($shipment->status),
                'location' => $context['location'] ?? implode(', ', array_filter([$shipment->destination_city, $shipment->destination_state])),
                'occurred_at' => now(), 'source' => $context['source'] ?? 'system',
            ]);
            $shipment->trackingContext = [];
            if (in_array($shipment->status, ['in_transit', 'out_for_delivery', 'delivered', 'exception', 'failed', 'cancelled'], true)) {
                $shipment->parcels()->update(['status' => $shipment->status, 'updated_at' => now()]);
            }
        });
    }

    protected function casts(): array
    {
        return ['weight' => 'decimal:3', 'metadata' => 'array', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function getLumoraTrackingIdAttribute(): string
    {
        return 'LM-TRK-'.str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function getCarrierDisplayAttribute(): string
    {
        return 'Lumora Express';
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function fulfillmentLocation(): BelongsTo { return $this->belongsTo(FulfillmentLocation::class); }
    public function parcels(): HasMany { return $this->hasMany(Parcel::class); }
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderByDesc('occurred_at')->orderByDesc('id');
    }
}