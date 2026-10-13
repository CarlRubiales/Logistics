<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookDelivery;
use App\Models\Store;
use App\Models\WebhookDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function index(): View
    {
        $store = Store::query()->where('is_active', true)->first();
        $deliveries = $store
            ? WebhookDelivery::query()->where('store_id', $store->id)->latest()->limit(12)->get()
            : collect();

        return view('integrations.index', [
            'store' => $store,
            'deliveries' => $deliveries,
            'stats' => [
                'processed' => $deliveries->where('status', 'processed')->count(),
                'received' => $deliveries->where('status', 'received')->count(),
                'failed' => $deliveries->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function toggle(Store $store): RedirectResponse
    {
        $store->update(['is_active' => ! $store->is_active]);

        return back()->with('status', $store->is_active ? 'Store connection activated.' : 'Store connection paused.');
    }

    public function retry(WebhookDelivery $delivery): RedirectResponse
    {
        $delivery->update(['status' => 'received', 'error' => null]);
        ProcessWebhookDelivery::dispatch($delivery->id);

        return back()->with('status', 'Webhook queued for another attempt.');
    }
}
