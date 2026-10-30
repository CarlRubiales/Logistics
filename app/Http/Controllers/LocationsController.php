<?php

namespace App\Http\Controllers;

use App\Models\FulfillmentLocation;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationsController extends Controller
{
    public function index(): View
    {
        $store = Store::query()->where('is_active', true)->first();
        $locations = $store?->locations()->withCount('shipments')->orderBy('name')->get() ?? collect();

        return view('locations.index', compact('locations', 'store'));
    }

    public function store(Request $request): RedirectResponse
    {
        $store = Store::query()->where('is_active', true)->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $store->locations()->create($data + ['is_active' => true]);

        return back()->with('status', 'Fulfillment location added.');
    }

    public function toggle(FulfillmentLocation $location): RedirectResponse
    {
        $location->update(['is_active' => ! $location->is_active]);

        return back()->with('status', $location->is_active ? 'Location activated.' : 'Location paused.');
    }
}
