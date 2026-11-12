<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpMessage;
use App\Models\Order;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\RiderProfile;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogisticsReturnApiController extends Controller
{
    /**
     * Handle return/refund notification from E-Commerce seller page.
     * Automatically assigns the return to the rider who delivered the item.
     */
    public function handleReturnRequest(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        $orderNumber = $request->input('order_number') ?: ($orderId ? ('#' . $orderId) : null);
        $trackingNumber = $request->input('tracking_number') ?: $request->input('barcode');
        $deliveringRiderName = $request->input('delivering_rider');
        $reason = $request->input('reason') ?: $request->input('reason_label', 'Return/Refund Request');
        $details = $request->input('details', '');
        $sellerNote = $request->input('seller_note');
        $items = $request->input('items', []);
        $evidence = $request->input('evidence', []);
        $customer = $request->input('customer', []);

        if (! $orderId && ! $orderNumber && ! $trackingNumber) {
            return response()->json(['error' => 'Order ID, number or tracking code is required.'], 422);
        }

        return DB::transaction(function () use (
            $orderId, $orderNumber, $trackingNumber, $deliveringRiderName,
            $reason, $details, $sellerNote, $items, $evidence, $customer, $request
        ) {
            // 1. Locate existing parcel
            $parcelQuery = Parcel::query();
            if ($orderId) {
                $parcelQuery->where('order_id', $orderId);
            }
            if ($orderNumber) {
                $parcelQuery->orWhere('parcel_number', $orderNumber)->orWhere('parcel_number', '#' . $orderId);
            }
            if ($trackingNumber) {
                $parcelQuery->orWhere('barcode', $trackingNumber);
            }

            $parcel = $parcelQuery->first();

            // 2. Identify the delivering rider
            $deliveringRiderId = null;
            $deliveringRiderObj = null;

            if ($parcel && $parcel->assigned_rider_id) {
                $deliveringRiderId = (int) $parcel->assigned_rider_id;
                $deliveringRiderObj = RiderProfile::with('user')->find($deliveringRiderId);
            }

            // Fallback: match by delivering rider name
            if (! $deliveringRiderObj && ! empty($deliveringRiderName)) {
                $deliveringRiderObj = RiderProfile::with('user')
                    ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$deliveringRiderName}%"))
                    ->first();

                if ($deliveringRiderObj) {
                    $deliveringRiderId = $deliveringRiderObj->id;
                }
            }

            // Fallback: find by branch or municipality
            if (! $deliveringRiderObj) {
                $branch = $parcel?->branch ?: ($customer['city'] ?? 'Pila');
                $deliveringRiderObj = RiderProfile::with('user')
                    ->forBranch($branch)
                    ->where('registration_status', 'approved')
                    ->first()
                    ?: RiderProfile::with('user')->where('registration_status', 'approved')->first();

                if ($deliveringRiderObj) {
                    $deliveringRiderId = $deliveringRiderObj->id;
                }
            }

            $riderDisplayName = $deliveringRiderObj?->user?->name ?: ($deliveringRiderName ?: 'Assigned Courier');

            // 3. Create parcel if it doesn't exist yet
            if (! $parcel) {
                $store = Store::where('is_active', true)->first();
                $storeId = $store?->id ?? 1;

                $orderRecord = Order::firstOrCreate(
                    ['order_number' => $orderNumber ?: ('ORD-' . $orderId)],
                    [
                        'store_id' => $storeId,
                        'external_id' => (string) $orderId,
                        'customer_name' => $customer['name'] ?? 'Buyer',
                        'customer_email' => $customer['email'] ?? null,
                        'status' => 'returned',
                        'shipping_address' => $customer,
                    ]
                );

                $parcel = Parcel::create([
                    'order_id' => $orderRecord->id,
                    'store_id' => $storeId,
                    'parcel_number' => $orderNumber ?: ('#' . $orderId),
                    'barcode' => $trackingNumber ?: ($orderNumber ?: ('#' . $orderId)),
                    'recipient_name' => $customer['name'] ?? 'Buyer',
                    'recipient_phone' => $customer['phone'] ?? null,
                    'address_line' => $customer['address'] ?? null,
                    'city' => $customer['city'] ?? 'Pila',
                    'province' => $customer['province'] ?? 'Laguna',
                    'branch' => $customer['city'] ?? 'Pila',
                    'status' => 'returned',
                    'assigned_rider_id' => $deliveringRiderId,
                    'received_at' => now(),
                ]);
            }

            // 4. Update parcel metadata with return items, seller approval, and courier assignment
            $metadata = is_array($parcel->metadata) ? $parcel->metadata : [];
            $metadata['is_return_refund'] = true;
            $metadata['return_status'] = 'approved';
            $metadata['return_approved_by'] = 'seller';
            $metadata['return_approved_at'] = now()->toIso8601String();
            $metadata['return_reported_at'] = now()->toIso8601String();
            $metadata['return_resolution'] = 'Approved by Seller · Assigned to Courier';
            $metadata['return_reason'] = $reason;
            $metadata['return_details'] = $details;
            $metadata['return_items'] = $items;
            $metadata['return_evidence'] = $evidence;
            $metadata['seller_note'] = $sellerNote;
            $metadata['assigned_rider_id'] = $deliveringRiderId;
            $metadata['delivering_rider_id'] = $deliveringRiderId;
            $metadata['assigned_rider_name'] = $riderDisplayName;

            // Maintain audit trail
            $audit = is_array($metadata['return_audit_trail'] ?? null) ? $metadata['return_audit_trail'] : [];
            $audit[] = [
                'actor' => 'Seller (' . ($request->input('seller_name') ?: 'Store Seller') . ')',
                'role' => 'seller',
                'action' => 'Approved Return Request',
                'status' => 'approved',
                'note' => "Seller approved return claim. Automatically assigned to courier {$riderDisplayName} for pickup.",
                'timestamp' => now()->toIso8601String(),
            ];
            $metadata['return_audit_trail'] = $audit;

            $parcel->update([
                'status' => 'returned',
                'assigned_rider_id' => $deliveringRiderId ?: $parcel->assigned_rider_id,
                'metadata' => $metadata,
            ]);

            // 5. Create parcel scan record
            ParcelScan::create([
                'parcel_id' => $parcel->id,
                'user_id' => $deliveringRiderObj?->user_id ?? (auth()->id() ?: 1),
                'scan_type' => 'returned',
                'barcode' => $parcel->barcode ?: $parcel->parcel_number,
                'result' => 'success',
                'note' => "Return approved by seller. Automatically assigned to courier {$riderDisplayName} for return intake.",
                'scanned_at' => now(),
            ]);

            // 6. Notify the rider via ErpMessage if available
            try {
                if (class_exists(ErpMessage::class) && $deliveringRiderId) {
                    $itemNames = collect($items)->pluck('name')->implode(', ');
                    $bodyMsg = "📦 Return Pickup Assigned: Order {$parcel->parcel_number} ({$itemNames}) from {$parcel->recipient_name}. Reason: {$reason}.";

                    ErpMessage::create([
                        'sender_id' => 1,
                        'rider_id' => $deliveringRiderId,
                        'parcel_id' => $parcel->id,
                        'body' => $bodyMsg,
                        'message_type' => 'system_alert',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::info('Rider return pickup ERP message skipped: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'parcel_id' => $parcel->id,
                'parcel_number' => $parcel->parcel_number,
                'status' => 'returned',
                'return_status' => 'approved',
                'assigned_rider_id' => $deliveringRiderId,
                'assigned_rider' => $riderDisplayName,
                'items_count' => count($items),
            ]);
        });
    }
}
