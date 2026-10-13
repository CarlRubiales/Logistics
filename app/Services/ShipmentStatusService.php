<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentStatusService
{
    public const STAGES = [
        'pending' => 'Received', 'packed' => 'Sorting Center', 'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered',
    ];
    public const EXCEPTIONS = ['exception', 'failed', 'cancelled'];

    public static function label(string $status): string
    {
        return self::STAGES[$status] ?? match ($status) {
            'exception' => 'Exception', 'failed' => 'Failed Delivery', 'cancelled' => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public static function description(string $status): string
    {
        return match ($status) {
            'pending' => 'Parcel successfully received by Lumora Logistics.',
            'packed' => 'Parcel is being processed at the sorting center.',
            'in_transit' => 'Parcel is currently on the way to its destination.',
            'out_for_delivery' => 'Parcel is out for final delivery.',
            'delivered' => 'Parcel has been delivered.',
            'exception', 'failed' => 'Delivery requires attention from the operations team.',
            'cancelled' => 'This shipment has been cancelled.',
            default => 'Shipment status updated.',
        };
    }

    public static function allowed(Shipment $shipment, string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }
        if (in_array($from, ['delivered', 'cancelled'], true)) {
            return false;
        }
        if (in_array($to, self::EXCEPTIONS, true)) {
            return true;
        }
        $stages = array_keys(self::STAGES);
        $next = array_search($to, $stages, true);
        if ($next === false) {
            return false;
        }
        $current = array_search($from, $stages, true);
        if ($current === false) {
            // Recover from an exception without undoing previously reached stages.
            $current = $shipment->trackingEvents->max(
                fn ($event) => ($rank = array_search($event->status, $stages, true)) === false ? -1 : $rank
            ) ?? -1;
        }
        return $next >= $current;
    }

    public function transition(Shipment $shipment, array $data, string $source = 'portal'): Shipment
    {
        return DB::transaction(function () use ($shipment, $data, $source) {
            $record = Shipment::whereKey($shipment->id)->lockForUpdate()->firstOrFail();
            if (! self::allowed($record, $record->status, $data['status'])) {
                throw ValidationException::withMessages(['status' => 'This status change would move the shipment backward or reopen a completed shipment.']);
            }
            if ($record->status === $data['status']) {
                return $record; // Retries and repeated submissions never add an event.
            }
            $record->trackingContext = [
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null, 'source' => $source,
            ];
            $record->update([
                'status' => $data['status'],
                'exception_type' => $data['exception_type'] ?? null,
                'exception_note' => $data['exception_note'] ?? null,
            ]);
            return $record;
        }, 3);
    }
}