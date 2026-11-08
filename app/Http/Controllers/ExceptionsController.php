<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\Shipment;
use App\Services\ParcelShipmentSyncService;
use App\Services\ShipmentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ExceptionsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $filterReason = trim((string) $request->string('reason'));
        $user = $request->user();
        $branch = $user?->getBranchName();

        // Query Return / Refund parcels scoped to the user's branch (or all branches for Master Admin)
        $parcelQuery = Parcel::with(['rider.user', 'area', 'shipment', 'order'])
            ->forBranch($branch)
            ->where(function ($q) {
                $q->whereIn('status', ['exception', 'failed', 'returned'])
                    ->orWhereNotNull('metadata->failure_reason')
                    ->orWhereNotNull('metadata->return_reason')
                    ->orWhere('metadata->is_return_refund', true);
            });

        if ($search !== '') {
            $parcelQuery->where(function ($q) use ($search) {
                $q->where('parcel_number', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('address_line', 'like', "%{$search}%")
                    ->orWhere('metadata->failure_reason', 'like', "%{$search}%")
                    ->orWhere('metadata->failure_notes', 'like', "%{$search}%");
            });
        }

        if ($filterReason !== '') {
            $parcelQuery->where(function ($q) use ($filterReason) {
                $q->where('metadata->failure_reason', 'like', "%{$filterReason}%")
                    ->orWhere('metadata->return_reason', 'like', "%{$filterReason}%");
            });
        }

        $returnParcels = (clone $parcelQuery)
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $allReturnParcels = (clone $parcelQuery)->get();

        $withMediaCount = $allReturnParcels->filter(function (Parcel $p) {
            $meta = $p->metadata ?? [];
            return ! empty($meta['return_photo_path'])
                || ! empty($meta['return_photo_data'])
                || ! empty($meta['return_video_path'])
                || ! empty($meta['return_video_data'])
                || ! empty($meta['proof_url'])
                || ! empty($meta['proof_video_url']);
        })->count();

        $damagedOrWrongCount = $allReturnParcels->filter(function (Parcel $p) {
            $reason = strtolower((string) ($p->metadata['return_reason'] ?? $p->metadata['failure_reason'] ?? ''));
            return str_contains($reason, 'damaged')
                || str_contains($reason, 'defective')
                || str_contains($reason, 'wrong item')
                || str_contains($reason, 'match');
        })->count();

        $refusedOrUnreachableCount = $allReturnParcels->filter(function (Parcel $p) {
            $reason = strtolower((string) ($p->metadata['return_reason'] ?? $p->metadata['failure_reason'] ?? ''));
            return str_contains($reason, 'refused')
                || str_contains($reason, 'cancelled')
                || str_contains($reason, 'unreachable')
                || str_contains($reason, 'not home');
        })->count();

        // Also include standalone Shipment exceptions if any
        $exceptions = Shipment::with(['order', 'location'])
            ->where('status', 'exception')
            ->when($branch, fn ($q) => $q->where('destination_city', 'like', "%{$branch}%"))
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('tracking_number', 'like', "%{$search}%")
                ->orWhere('external_id', 'like', "%{$search}%")
                ->orWhere('exception_type', 'like', "%{$search}%")
                ->orWhere('exception_note', 'like', "%{$search}%")
                ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"))
            ))
            ->latest('updated_at')
            ->paginate(10, ['*'], 'shipment_page')
            ->withQueryString();

        return view('exceptions.index', [
            'returnParcels' => $returnParcels,
            'exceptions' => $exceptions,
            'search' => $search,
            'filterReason' => $filterReason,
            'branch' => $branch,
            'stats' => [
                'open' => $allReturnParcels->count(),
                'with_media' => $withMediaCount,
                'damaged_or_wrong' => $damagedOrWrongCount,
                'refused_or_unreachable' => $refusedOrUnreachableCount,
            ],
        ]);
    }

    public function resolveParcel(Request $request, Parcel $parcel, ParcelShipmentSyncService $syncService): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve_return_refund,receive_returned_parcel,out_for_delivery,in_transit,cancelled'],
            'resolution_note' => ['nullable', 'string', 'max:500'],
        ]);

        $action = $validated['action'];
        $metadata = is_array($parcel->metadata) ? $parcel->metadata : [];
        $operatorName = $request->user()?->name ?? 'Logistics Admin';

        $resolutionLabels = [
            'approve_return_refund' => 'Return / Refund Approved',
            'receive_returned_parcel' => 'Returned Parcel Received at Hub',
            'out_for_delivery' => 'Re-dispatched Out for Delivery',
            'in_transit' => 'Resumed Transit',
            'cancelled' => 'Cancelled / Refunded',
        ];

        $newStatus = match ($action) {
            'approve_return_refund', 'receive_returned_parcel' => 'exception',
            'out_for_delivery' => 'out_for_delivery',
            'in_transit' => 'in_transit',
            'cancelled' => 'cancelled',
        };

        $metadata['return_resolution'] = $resolutionLabels[$action] ?? $action;
        $metadata['return_resolved_by'] = $operatorName;
        $metadata['return_resolved_at'] = now()->toIso8601String();
        if (! empty($validated['resolution_note'])) {
            $metadata['return_resolution_note'] = $validated['resolution_note'];
        }

        if (in_array($action, ['out_for_delivery', 'in_transit'], true)) {
            $metadata['is_return_refund'] = false;
        } else {
            $metadata['is_return_refund'] = true;
        }

        $parcel->update([
            'status' => $newStatus,
            'metadata' => $metadata,
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()?->id,
            'scan_type' => $newStatus,
            'barcode' => $parcel->barcode ?: $parcel->parcel_number,
            'result' => 'success',
            'note' => "Return/Refund action [{$metadata['return_resolution']}] recorded by {$operatorName}"
                . (! empty($validated['resolution_note']) ? ": {$validated['resolution_note']}" : ''),
            'scanned_at' => now(),
        ]);

        $syncService->sync($parcel->fresh());

        return back()->with('status', "Parcel {$parcel->parcel_number} updated: {$metadata['return_resolution']}.");
    }

    public function resolve(Request $request, Shipment $shipment, ShipmentStatusService $service): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', 'in:in_transit,pending,cancelled']]);
        $service->transition($shipment, [
            'status' => $validated['status'],
            'label' => 'Return / Refund updated',
            'description' => 'Return / Refund status updated to ' . str_replace('_', ' ', $validated['status']) . ' by operations.',
        ], 'exceptions');

        return back()->with('status', 'Return / Refund updated for ' . ($shipment->tracking_number ?: $shipment->external_id) . '.');
    }

    /**
     * Serve Return / Refund Picture (photo) or Video for a parcel.
     */
    public function media(Request $request, Parcel $parcel, string $type): Response
    {
        if (! in_array($type, ['photo', 'video'], true)) {
            abort(404);
        }

        $metadata = is_array($parcel->metadata) ? $parcel->metadata : [];
        $pathKey = $type === 'photo' ? 'return_photo_path' : 'return_video_path';
        $dataKey = $type === 'photo' ? 'return_photo_data' : 'return_video_data';
        $urlKey = $type === 'photo' ? 'proof_url' : 'proof_video_url';

        // 1. Check public disk storage first
        $storedPath = $metadata[$pathKey] ?? null;
        if (is_string($storedPath) && $storedPath !== '' && Storage::disk('public')->exists($storedPath)) {
            $fullPath = Storage::disk('public')->path($storedPath);
            $mime = Storage::disk('public')->mimeType($storedPath) ?: ($type === 'photo' ? 'image/jpeg' : 'video/mp4');
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        // 2. Fallback to base64 data stored in parcel metadata (survives ephemeral container restarts)
        $dataUri = $metadata[$dataKey] ?? null;
        if (! $dataUri && is_string($metadata[$urlKey] ?? null) && str_starts_with($metadata[$urlKey], 'data:')) {
            $dataUri = $metadata[$urlKey];
        }

        if (is_string($dataUri) && $dataUri !== '') {
            $mime = $type === 'photo' ? 'image/jpeg' : 'video/mp4';
            $rawB64 = $dataUri;
            if (preg_match('/^data:([a-zA-Z0-9\/+.-]+);base64,(.*)$/s', $dataUri, $matches)) {
                $mime = $matches[1];
                $rawB64 = $matches[2];
            }
            $binary = base64_decode($rawB64, true);
            if ($binary !== false && strlen($binary) > 0) {
                return response($binary, 200, [
                    'Content-Type' => $mime,
                    'Content-Length' => (string) strlen($binary),
                    'Cache-Control' => 'public, max-age=86400',
                    'Accept-Ranges' => 'bytes',
                ]);
            }
        }

        // 3. If external HTTP URL was stored
        $extUrl = $metadata[$urlKey] ?? null;
        if (is_string($extUrl) && (str_starts_with($extUrl, 'http://') || str_starts_with($extUrl, 'https://'))) {
            return redirect()->away($extUrl);
        }

        abort(404, 'Return/Refund media not found.');
    }
}
