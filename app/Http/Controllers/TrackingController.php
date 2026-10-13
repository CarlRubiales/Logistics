<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function index(Request $request, ?string $trackingNumber = null): View
    {
        $trackingNumber = trim((string) ($trackingNumber ?? $request->query('tracking', '')));
        $shipment = null;
        $notFound = false;

        if ($trackingNumber !== '') {
            $shipment = Shipment::query()
                ->with(['order', 'trackingEvents'])
                ->where(function ($query) use ($trackingNumber) {
                    $query->where('tracking_number', $trackingNumber);
                    if (preg_match('/^LM-TRK-0*(\d+)$/i', $trackingNumber, $matches)) {
                        $query->orWhere('id', $matches[1]);
                    }
                })
                ->first();
            $notFound = $shipment === null;
        }

        $currentStage = $shipment ? $this->stageFor($shipment->status) : 0;
        $stages = [
            ['key' => 'received', 'label' => 'Order placed'],
            ['key' => 'packed', 'label' => 'Shipped'],
            ['key' => 'in_transit', 'label' => 'Out for delivery'],
            ['key' => 'delivered', 'label' => 'Delivered'],
        ];

        return view('tracking.index', compact('shipment', 'trackingNumber', 'notFound', 'currentStage', 'stages'));
    }

    private function stageFor(?string $status): int
    {
        return match ($status) {
            'delivered' => 3,
            'in_transit', 'out_for_delivery' => 2,
            'packed' => 1,
            'pending' => 0,
            'exception', 'cancelled' => 0,
            default => 0,
        };
    }
}
