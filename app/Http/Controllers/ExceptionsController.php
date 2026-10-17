<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExceptionsController extends Controller
{
    public function index(Request $request): View
    {
        $store = Store::where('is_active', true)->first();
        $search = trim($request->string('search')->toString());
        $branch = $request->user()?->getBranchName();
        $base = Shipment::query()->where(fn ($q) => $q->whereIn('status', ['exception', 'failed'])->orWhereNotNull('exception_type'))
            ->when($store, fn ($q) => $q->whereHas('order', fn ($q) => $q->where('store_id', $store->id)));
        if ($branch) {
            $base->where(function ($q) use ($branch) {
                $q->where('destination_city', 'like', "%{$branch}%")
                  ->orWhereHas('parcels', fn ($pq) => $pq->forBranch($branch));
            });
        }
        $exceptions = (clone $base)->with('order')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('tracking_number', 'like', "%{$search}%")->orWhere('exception_type', 'like', "%{$search}%")->orWhere('destination_city', 'like', "%{$search}%")->orWhereHas('order', fn ($q) => $q->where('order_number', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"))))
            ->latest()->paginate(12)->withQueryString();
        return view('exceptions.index', [
            'exceptions' => $exceptions,
            'stats' => ['open' => (clone $base)->whereIn('status', ['exception', 'failed'])->count(), 'address' => (clone $base)->where('exception_type', 'address')->count(), 'carrier' => (clone $base)->where('exception_type', 'carrier_delay')->count()],
            'search' => $search, 'store' => $store,
        ]);
    }

    public function resolve(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'packed', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'])],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        app(\App\Services\ShipmentStatusService::class)->transition($shipment, $data);
        return back()->with('status', 'Shipment exception resolved.');
    }
}