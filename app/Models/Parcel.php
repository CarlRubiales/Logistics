<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Parcel $parcel) {
            if (($parcel->shipment_id || $parcel->parcel_number) && ($parcel->wasRecentlyCreated || $parcel->wasChanged('status'))) {
                app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel);
            }
        });
    }

    protected $attributes = ['status' => 'received'];
    protected $fillable = ['order_id', 'shipment_id', 'store_id', 'destination_area_id', 'branch', 'assigned_rider_id', 'parcel_number', 'barcode', 'recipient_name', 'recipient_phone', 'address_line', 'city', 'province', 'postal_code', 'status', 'sort_bin', 'received_at', 'scanned_at', 'sorted_at', 'assigned_at', 'rider_received_at', 'metadata'];
    protected function casts(): array { return ['received_at' => 'datetime', 'scanned_at' => 'datetime', 'sorted_at' => 'datetime', 'assigned_at' => 'datetime', 'rider_received_at' => 'datetime', 'metadata' => 'array']; }

    public function scopeForBranch($query, ?string $branch)
    {
        if (! $branch) {
            return $query;
        }
        return $query->where('branch', $branch);
    }

    public function store() { return $this->belongsTo(Store::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function shipment() { return $this->belongsTo(Shipment::class); }
    public function area() { return $this->belongsTo(DestinationArea::class, 'destination_area_id'); }
    public function rider() { return $this->belongsTo(RiderProfile::class, 'assigned_rider_id'); }
    public function scans() { return $this->hasMany(ParcelScan::class); }
    public function messages() { return $this->hasMany(ErpMessage::class); }
}
