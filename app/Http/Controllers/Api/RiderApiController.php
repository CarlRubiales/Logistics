<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionTransaction;
use App\Models\ErpMessage;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RiderApiController extends Controller
{
    /**
     * Authenticate rider and return API bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $passwordMatches = false;
        if (str_starts_with($user->password, '$2y$') || str_starts_with($user->password, '$2a$') || str_starts_with($user->password, '$argon2')) {
            $passwordMatches = Hash::check($validated['password'], $user->password);
        } else {
            if ($validated['password'] === $user->password) {
                $passwordMatches = true;
                // Automatically upgrade plain-text password to bcrypt
                $user->update(['password' => Hash::make($validated['password'])]);
            }
        }

        if (! $passwordMatches) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->role !== 'rider') {
            return response()->json([
                'status' => 'error',
                'message' => 'This account does not have rider access.',
            ], 403);
        }

        $profile = $user->riderProfile;

        // Ensure rider profile exists
        if (! $profile) {
            $profile = RiderProfile::create([
                'user_id' => $user->id,
                'registration_status' => 'approved',
                'availability' => 'offline',
            ]);
        }

        // Check if rider registration is approved
        if ($profile->registration_status === 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Your rider account is awaiting administrator approval in the logistics portal.',
            ], 403);
        }

        if ($profile->registration_status === 'disapproved' || $profile->registration_status === 'rejected') {
            return response()->json([
                'status' => 'error',
                'message' => 'Your rider registration was not approved. Please contact logistics support.',
            ], 403);
        }

        // Revoke previous mobile tokens if any
        $user->tokens()->where('name', 'flutter-rider-app')->delete();
        $token = $user->createToken('flutter-rider-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $profile->phone ?? '',
                'vehicle_type' => $profile->vehicle_type ?? 'Motorcycle',
                'license_number' => $profile->license_number ?? '',
                'availability' => $profile->availability ?? 'offline',
                'registration_status' => $profile->registration_status ?? 'approved',
            ],
        ]);
    }

    /**
     * Register a new rider.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:40'],
            'vehicle_type' => ['nullable', 'string', 'max:80'],
            'license_number' => ['nullable', 'string', 'max:80'],
        ]);

        $vehicleType = match (strtolower(trim($validated['vehicle_type'] ?? 'motorcycle'))) {
            'tricycle' => 'Tricycle',
            default => 'Motorcycle',
        };

        // Handle Driver's License upload (file or base64)
        $licensePath = null;
        if ($request->hasFile('license_photo')) {
            $licensePath = $request->file('license_photo')->store('rider_documents', 'public');
        } elseif ($request->hasFile('drivers_license')) {
            $licensePath = $request->file('drivers_license')->store('rider_documents', 'public');
        } elseif ($request->filled('license_photo') || $request->filled('drivers_license')) {
            $raw = $request->input('license_photo') ?: $request->input('drivers_license');
            if (str_starts_with($raw, 'http') || str_starts_with($raw, 'rider_documents')) {
                $licensePath = $raw;
            } else {
                $cleaned = preg_replace('#^data:image/\w+;base64,#i', '', $raw);
                $binary = base64_decode($cleaned, true);
                if ($binary !== false && strlen($binary) > 0) {
                    $name = 'license_' . Str::uuid() . '.jpg';
                    Storage::disk('public')->put('rider_documents/' . $name, $binary);
                    $licensePath = 'rider_documents/' . $name;
                }
            }
        }

        // Handle OR/CR upload (file or base64)
        $orCrPath = null;
        if ($request->hasFile('or_cr_photo')) {
            $orCrPath = $request->file('or_cr_photo')->store('rider_documents', 'public');
        } elseif ($request->hasFile('or_cr')) {
            $orCrPath = $request->file('or_cr')->store('rider_documents', 'public');
        } elseif ($request->filled('or_cr_photo') || $request->filled('or_cr')) {
            $raw = $request->input('or_cr_photo') ?: $request->input('or_cr');
            if (str_starts_with($raw, 'http') || str_starts_with($raw, 'rider_documents')) {
                $orCrPath = $raw;
            } else {
                $cleaned = preg_replace('#^data:image/\w+;base64,#i', '', $raw);
                $binary = base64_decode($cleaned, true);
                if ($binary !== false && strlen($binary) > 0) {
                    $name = 'or_cr_' . Str::uuid() . '.jpg';
                    Storage::disk('public')->put('rider_documents/' . $name, $binary);
                    $orCrPath = 'rider_documents/' . $name;
                }
            }
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'rider',
        ]);

        $profile = RiderProfile::create([
            'user_id' => $user->id,
            'phone' => $validated['phone'] ?? null,
            'vehicle_type' => $vehicleType,
            'license_number' => $validated['license_number'] ?? null,
            'license_photo_path' => $licensePath,
            'or_cr_photo_path' => $orCrPath,
            'registration_status' => 'pending',
            'availability' => 'offline',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Registration submitted successfully. Awaiting administrator approval.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'vehicle_type' => $profile->vehicle_type,
                'registration_status' => $profile->registration_status,
                'has_license' => ! empty($profile->license_photo_path),
                'has_or_cr' => ! empty($profile->or_cr_photo_path),
            ],
        ], 201);
    }

    /**
     * Return authenticated rider info and profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $profile->phone ?? '',
                'vehicle_type' => $profile->vehicle_type ?? 'Motorcycle',
                'license_number' => $profile->license_number ?? '',
                'availability' => $profile->availability ?? 'offline',
                'registration_status' => $profile->registration_status ?? 'approved',
            ],
        ]);
    }

    /**
     * Update rider availability (online / offline).
     */
    public function updateAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'available' => ['sometimes', 'boolean'],
            'availability' => ['sometimes', 'string', 'in:online,offline'],
        ]);

        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'error', 'message' => 'Rider profile not found.'], 404);
        }

        $isOnline = isset($validated['available'])
            ? (bool) $validated['available']
            : (($validated['availability'] ?? '') === 'online');

        $profile->update([
            'availability' => $isOnline ? 'online' : 'offline',
        ]);

        return response()->json([
            'status' => 'success',
            'availability' => $profile->availability,
            'is_online' => $isOnline,
        ]);
    }

    /**
     * Return deliveries assigned to the authenticated rider.
     */
    public function deliveries(Request $request): JsonResponse
    {
        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $parcels = Parcel::where('assigned_rider_id', $profile->id)
            ->latest('assigned_at')
            ->get();

        $data = $parcels->values()->map(function (Parcel $p, int $idx) {
            $transformed = $this->transformParcel($p);
            if (empty($transformed['stop_order']) || $transformed['stop_order'] <= 1) {
                $transformed['stop_order'] = $idx + 1;
            }
            return $transformed;
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Return available deliveries that can be accepted.
     */
    public function availableDeliveries(Request $request): JsonResponse
    {
        $parcels = Parcel::whereNull('assigned_rider_id')
            ->whereIn('status', ['received', 'sorted', 'pending'])
            ->latest()
            ->take(20)
            ->get();

        $data = $parcels->map(fn (Parcel $p) => $this->transformParcel($p));

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Accept a delivery request.
     */
    public function acceptDelivery(Request $request, $id): JsonResponse
    {
        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json(['status' => 'error', 'message' => 'Rider profile not found.'], 404);
        }

        $parcel = Parcel::where('id', $id)
            ->orWhere('parcel_number', $id)
            ->first();

        if (! $parcel) {
            return response()->json(['status' => 'error', 'message' => 'Parcel not found.'], 404);
        }

        $parcel->update([
            'assigned_rider_id' => $profile->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()->id,
            'scan_type' => 'assigned',
            'barcode' => $parcel->barcode,
            'result' => 'success',
            'note' => 'Accepted by rider ' . $request->user()->name,
            'scanned_at' => now(),
        ]);

        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        return response()->json([
            'status' => 'success',
            'data' => $this->transformParcel($parcel),
        ]);
    }

    /**
     * Update delivery progress stage (pickedUp / out_for_delivery / delivered / failed).
     */
    public function updateDeliveryStatus(Request $request, $id): JsonResponse
    {
        $rawStatus = $request->input('status') ?? $request->input('stage') ?? 'in_transit';
        $dbStatus = $this->mapToDbStatus($rawStatus);

        $parcel = $this->findParcel($id);

        if (! $parcel) {
            return response()->json(['status' => 'error', 'message' => 'Parcel not found.'], 404);
        }

        // Merge incoming payload into parcel metadata
        $metadata = $parcel->metadata ?? [];
        $metadata['last_status_code'] = $rawStatus;
        if ($request->filled('latitude')) {
            $metadata['latitude'] = (float) $request->input('latitude');
        }
        if ($request->filled('longitude')) {
            $metadata['longitude'] = (float) $request->input('longitude');
        }
        if ($request->filled('failureReason') || $request->filled('failure_reason')) {
            $metadata['failure_reason'] = $request->input('failureReason') ?? $request->input('failure_reason');
        }
        if ($request->filled('failureNotes') || $request->filled('failure_notes')) {
            $metadata['failure_notes'] = $request->input('failureNotes') ?? $request->input('failure_notes');
        }
        if ($request->filled('proofUrl') || $request->filled('proof_url')) {
            $metadata['proof_url'] = $request->input('proofUrl') ?? $request->input('proof_url');
        }
        if ($request->filled('codAmount') || $request->filled('cod_amount')) {
            $metadata['cod_amount'] = (float) ($request->input('codAmount') ?? $request->input('cod_amount'));
            $metadata['is_cod'] = true;
        }
        $metadata['last_updated_at'] = $request->input('timestamp', now()->toIso8601String());

        $parcel->update([
            'status' => $dbStatus,
            'metadata' => $metadata,
            'rider_received_at' => in_array($dbStatus, ['rider_received', 'in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at ? now() : $parcel->rider_received_at,
        ]);

        $riderName = $request->user()?->name ?? ($parcel->rider?->user?->name ?? 'Courier');
        $noteText = 'Status updated to ' . ucwords(str_replace('_', ' ', $dbStatus)) . ' by rider ' . $riderName;
        if (! empty($metadata['failure_reason'])) {
            $noteText .= ' (Reason: ' . $metadata['failure_reason'] . ')';
        }
        if (isset($metadata['latitude']) && isset($metadata['longitude'])) {
            $noteText .= " [GPS: {$metadata['latitude']}, {$metadata['longitude']}]";
        }

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()?->id ?? $parcel->rider?->user_id,
            'scan_type' => $dbStatus,
            'barcode' => $parcel->barcode ?: $parcel->parcel_number,
            'result' => 'success',
            'note' => $noteText,
            'scanned_at' => now(),
        ]);

        // Explicitly trigger shipment tracking sync
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        // If delivered, record commission transaction if none exists yet
        if ($dbStatus === 'delivered' && $parcel->assigned_rider_id) {
            CommissionTransaction::firstOrCreate(
                [
                    'rider_profile_id' => $parcel->assigned_rider_id,
                    'parcel_id' => $parcel->id,
                ],
                [
                    'amount' => 150.00,
                    'status' => 'pending',
                    'description' => 'Delivery payout for ' . $parcel->parcel_number,
                    'earned_at' => now(),
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Parcel {$parcel->parcel_number} status updated to " . ucwords(str_replace('_', ' ', $dbStatus)),
            'data' => $this->transformParcel($parcel->fresh(['area', 'shipment'])),
        ]);
    }

    /**
     * Return rider earnings and commission history.
     */
    public function earnings(Request $request): JsonResponse
    {
        $profile = $request->user()->riderProfile;

        if (! $profile) {
            return response()->json([
                'status' => 'success',
                'today' => '₱0.00',
                'balance' => '₱0.00',
                'completed' => '0 deliveries completed',
                'history' => [],
            ]);
        }

        $todayStart = now()->startOfDay();

        $todayEarnings = CommissionTransaction::where('rider_profile_id', $profile->id)
            ->where('earned_at', '>=', $todayStart)
            ->sum('amount');

        $totalBalance = CommissionTransaction::where('rider_profile_id', $profile->id)
            ->where('status', 'pending')
            ->sum('amount');

        $todayDeliveriesCount = Parcel::where('assigned_rider_id', $profile->id)
            ->where('status', 'delivered')
            ->where('updated_at', '>=', $todayStart)
            ->count();

        $historyTransactions = CommissionTransaction::where('rider_profile_id', $profile->id)
            ->with('parcel')
            ->latest('earned_at')
            ->take(10)
            ->get();

        $history = $historyTransactions->map(function ($tx) {
            return [
                'id' => $tx->parcel?->parcel_number ?? ('TX-' . $tx->id),
                'store' => $tx->description ?? 'Standard Delivery',
                'fee' => '₱' . number_format($tx->amount, 2),
                'date' => $tx->earned_at ? $tx->earned_at->format('M d, Y') : now()->format('M d, Y'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'today' => '₱' . number_format($todayEarnings, 2),
            'balance' => '₱' . number_format($totalBalance, 2),
            'completed' => "{$todayDeliveriesCount} deliveries completed",
            'history' => $history,
        ]);
    }

    /**
     * Logout and revoke active token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return assigned parcels (alias for mobile scanner contract).
     */
    public function parcels(Request $request): JsonResponse
    {
        return $this->deliveries($request);
    }

    /**
     * Look up one parcel by barcode, parcel_number, ID, or tracking number.
     */
    public function showParcel(Request $request, $code): JsonResponse
    {
        $parcel = $this->findParcel($code);

        if (! $parcel) {
            return response()->json([
                'status' => 'error',
                'message' => "Parcel with code '{$code}' not found.",
            ], 404);
        }

        $data = $this->transformParcel($parcel);
        $data['scans'] = $parcel->scans()->latest('scanned_at')->take(10)->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Record a scan event (intake, handoff, scan, delivery, exception).
     */
    public function scanParcel(Request $request, $code): JsonResponse
    {
        $validated = $request->validate([
            'scan_type' => ['sometimes', 'string'],
            'barcode' => ['sometimes', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $parcel = $this->findParcel($code);
        if (! $parcel) {
            return response()->json([
                'status' => 'error',
                'message' => "Parcel with code '{$code}' not found.",
            ], 404);
        }

        $profile = $request->user()->riderProfile;
        $scanType = $validated['scan_type'] ?? 'scan';
        $note = $validated['notes'] ?? $validated['note'] ?? ('Scanned (' . $scanType . ') by rider ' . $request->user()->name);

        // Auto-assign to rider if parcel is unassigned
        if (! $parcel->assigned_rider_id && $profile) {
            $parcel->update([
                'assigned_rider_id' => $profile->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
        }

        $newStatus = match ($scanType) {
            'handoff', 'intake', 'pickup', 'picked_up', 'rider_received' => 'rider_received',
            'out_for_delivery' => 'out_for_delivery',
            'delivery', 'delivered' => 'delivered',
            'exception' => 'exception',
            default => $parcel->status,
        };

        $updateData = ['status' => $newStatus];
        if (! empty($validated['barcode']) && ! $parcel->barcode) {
            $updateData['barcode'] = $validated['barcode'];
        }
        if (in_array($newStatus, ['rider_received', 'in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at) {
            $updateData['rider_received_at'] = now();
        }

        $parcel->update($updateData);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()->id,
            'scan_type' => $scanType,
            'barcode' => $parcel->barcode ?: ($validated['barcode'] ?? null),
            'result' => 'success',
            'note' => $note,
            'scanned_at' => now(),
        ]);

        // Explicitly trigger shipment tracking sync
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        if ($newStatus === 'delivered' && $parcel->assigned_rider_id) {
            CommissionTransaction::firstOrCreate(
                [
                    'rider_profile_id' => $parcel->assigned_rider_id,
                    'parcel_id' => $parcel->id,
                ],
                [
                    'amount' => 150.00,
                    'status' => 'pending',
                    'description' => 'Delivery payout for ' . $parcel->parcel_number,
                    'earned_at' => now(),
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Parcel {$parcel->parcel_number} scan recorded.",
            'data' => $this->transformParcel($parcel->fresh(['area', 'shipment'])),
        ]);
    }

    /**
     * Update tracking status directly via mobile scanner / API.
     */
    public function updateParcelStatus(Request $request, $code): JsonResponse
    {
        $parcel = $this->findParcel($code);
        if (! $parcel) {
            return response()->json([
                'status' => 'error',
                'message' => "Parcel with code '{$code}' not found.",
            ], 404);
        }

        $rawStatus = $request->input('status') ?? $request->input('stage') ?? 'in_transit';
        $dbStatus = $this->mapToDbStatus($rawStatus);

        $metadata = $parcel->metadata ?? [];
        $metadata['last_status_code'] = $rawStatus;
        if ($request->filled('latitude')) {
            $metadata['latitude'] = (float) $request->input('latitude');
        }
        if ($request->filled('longitude')) {
            $metadata['longitude'] = (float) $request->input('longitude');
        }
        if ($request->filled('failureReason') || $request->filled('failure_reason')) {
            $metadata['failure_reason'] = $request->input('failureReason') ?? $request->input('failure_reason');
        }
        if ($request->filled('failureNotes') || $request->filled('failure_notes')) {
            $metadata['failure_notes'] = $request->input('failureNotes') ?? $request->input('failure_notes');
        }
        if ($request->filled('proofUrl') || $request->filled('proof_url')) {
            $metadata['proof_url'] = $request->input('proofUrl') ?? $request->input('proof_url');
        }
        if ($request->filled('codAmount') || $request->filled('cod_amount')) {
            $metadata['cod_amount'] = (float) ($request->input('codAmount') ?? $request->input('cod_amount'));
            $metadata['is_cod'] = true;
        }
        $metadata['last_updated_at'] = $request->input('timestamp', now()->toIso8601String());

        $riderName = $request->user()?->name ?? ($parcel->rider?->user?->name ?? 'Courier');
        $note = $request->input('notes') ?? $request->input('note');
        if (! $note) {
            $note = 'Status updated to ' . ucwords(str_replace('_', ' ', $dbStatus)) . ' by rider ' . $riderName;
            if (! empty($metadata['failure_reason'])) {
                $note .= ' (Reason: ' . $metadata['failure_reason'] . ')';
            }
            if (isset($metadata['latitude']) && isset($metadata['longitude'])) {
                $note .= " [GPS: {$metadata['latitude']}, {$metadata['longitude']}]";
            }
        }

        $parcel->update([
            'status' => $dbStatus,
            'metadata' => $metadata,
            'rider_received_at' => in_array($dbStatus, ['rider_received', 'in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at ? now() : $parcel->rider_received_at,
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()?->id ?? $parcel->rider?->user_id,
            'scan_type' => $dbStatus,
            'barcode' => $parcel->barcode ?: $parcel->parcel_number,
            'result' => 'success',
            'note' => $note,
            'scanned_at' => now(),
        ]);

        // Explicitly trigger shipment tracking sync
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        if ($dbStatus === 'delivered' && $parcel->assigned_rider_id) {
            CommissionTransaction::firstOrCreate(
                [
                    'rider_profile_id' => $parcel->assigned_rider_id,
                    'parcel_id' => $parcel->id,
                ],
                [
                    'amount' => 150.00,
                    'status' => 'pending',
                    'description' => 'Delivery payout for ' . $parcel->parcel_number,
                    'earned_at' => now(),
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Parcel {$parcel->parcel_number} status updated to " . ucwords(str_replace('_', ' ', $dbStatus)) . ".",
            'data' => $this->transformParcel($parcel->fresh(['area', 'shipment'])),
        ]);
    }

    /**
     * Return rider messages with operations / sorting center staff.
     */
    public function messages(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $query = ErpMessage::with(['sender', 'recipient', 'parcel'])
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('recipient_id', $userId);
            });

        if ($request->filled('parcel_id')) {
            $query->where('parcel_id', $request->input('parcel_id'));
        } elseif ($request->filled('code')) {
            $parcel = $this->findParcel($request->input('code'));
            if ($parcel) {
                $query->where('parcel_id', $parcel->id);
            }
        }

        $messages = $query->latest()->take(50)->get()->reverse()->values();

        $data = $messages->map(function ($msg) use ($userId) {
            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $msg->sender?->name ?? 'System',
                'recipient_id' => $msg->recipient_id,
                'recipient_name' => $msg->recipient?->name ?? 'Sorting Center',
                'is_mine' => $msg->sender_id === $userId,
                'message' => $msg->message,
                'text' => $msg->message,
                'parcel_id' => $msg->parcel_id,
                'parcel_number' => $msg->parcel?->parcel_number,
                'created_at' => $msg->created_at?->toIso8601String(),
                'time' => $msg->created_at ? $msg->created_at->format('h:i A') : '',
                'date' => $msg->created_at ? $msg->created_at->format('M d, Y') : '',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Send a message from rider to operations / sorting center staff.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $rawMessage = $request->input('message') ?? $request->input('text') ?? '';
        if (empty(trim($rawMessage))) {
            return response()->json(['status' => 'error', 'message' => 'Message is required.'], 422);
        }

        $recipientId = $request->input('recipient_id') ?? User::whereIn('role', ['admin', 'operations_manager'])->orderBy('id')->value('id');
        if (! $recipientId) {
            $recipientId = User::where('role', 'admin')->value('id') ?? 1;
        }

        $parcelId = null;
        $parcelCode = $request->input('parcel_id') ?? $request->input('parcelId');
        if (! empty($parcelCode)) {
            $parcel = $this->findParcel($parcelCode);
            $parcelId = $parcel?->id;
        }

        $msg = ErpMessage::create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipientId,
            'parcel_id' => $parcelId,
            'message' => $rawMessage,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'sender_name' => $request->user()->name,
                'is_mine' => true,
                'message' => $msg->message,
                'text' => $msg->message,
                'parcel_id' => $msg->parcel_id,
                'created_at' => $msg->created_at?->toIso8601String(),
                'time' => $msg->created_at->format('h:i A'),
            ],
        ], 201);
    }

    /**
     * Return conversation channels for the rider.
     */
    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $lastSupportMessage = ErpMessage::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)->orWhere('recipient_id', $userId);
        })->latest()->first();

        $unreadCount = ErpMessage::where('recipient_id', $userId)->whereNull('read_at')->count();

        $conversations = [
            [
                'id' => 'operations',
                'name' => 'Sorting Center Operations',
                'description' => 'Rider Support',
                'unread' => $unreadCount,
                'preview' => $lastSupportMessage ? $lastSupportMessage->message : 'Contact sorting center operations',
                'updated_at' => $lastSupportMessage ? $lastSupportMessage->created_at->toIso8601String() : now()->toIso8601String(),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $conversations,
        ]);
    }

    /**
     * Map arbitrary status strings (ERP codes, camelCase, snake_case, legacy) to parcel DB status.
     */
    private function mapToDbStatus(?string $input): string
    {
        if (empty($input)) {
            return 'in_transit';
        }

        $normalized = strtolower(str_replace(['-', ' '], '_', trim($input)));

        return match ($normalized) {
            'assigned_to_rider', 'assigned', 'accepted', 'pending' => 'assigned',
            'picked_up_from_hub', 'pickedup', 'picked_up', 'pickup', 'picked', 'rider_received', 'sorted' => 'rider_received',
            'out_for_delivery', 'outfordelivery', 'arrived', 'arrived_at_stop' => 'out_for_delivery',
            'delivered', 'delivery', 'completed' => 'delivered',
            'delivery_failed', 'failed', 'exception', 'rescheduled' => 'exception',
            'returning_to_hub', 'returning', 'in_transit', 'intransit' => 'in_transit',
            'returned_to_hub', 'returned', 'hub_returned' => 'exception',
            default => 'in_transit',
        };
    }

    /**
     * Find parcel by barcode, parcel_number, ID, or shipment tracking number.
     */
    protected function findParcel($code): ?Parcel
    {
        if (empty($code)) {
            return null;
        }

        $clean = ltrim((string) $code, '#');

        $shipmentId = null;
        if (preg_match('/^LM-TRK-0*(\d+)$/i', $code, $matches)) {
            $shipmentId = (int) $matches[1];
        }

        return Parcel::where('parcel_number', $code)
            ->orWhere('parcel_number', $clean)
            ->orWhere('parcel_number', '#' . $clean)
            ->orWhere('barcode', $code)
            ->orWhere('barcode', $clean)
            ->orWhere('id', is_numeric($clean) ? (int) $clean : -1)
            ->orWhere(function ($q) use ($code, $clean, $shipmentId) {
                $q->whereHas('shipment', function ($sq) use ($code, $clean, $shipmentId) {
                    $sq->where('tracking_number', $code)
                        ->orWhere('tracking_number', $clean);
                    if ($shipmentId) {
                        $sq->orWhere('id', $shipmentId);
                    }
                });
            })
            ->first();
    }

    /**
     * Helper to transform parcel into Flutter & Scanner compatible JSON.
     */
    private function transformParcel(Parcel $p): array
    {
        $dropoff = $p->address_line ?: ($p->city . ', ' . $p->province);
        $metadata = $p->metadata ?? [];
        $barcode = ! empty($p->barcode) ? $p->barcode : $p->parcel_number;
        $areaName = $p->area?->name ?? 'Central Area';

        $statusCode = match ($p->status) {
            'assigned' => 'ASSIGNED_TO_RIDER',
            'rider_received' => 'PICKED_UP_FROM_HUB',
            'in_transit' => 'PICKED_UP_FROM_HUB',
            'out_for_delivery' => 'OUT_FOR_DELIVERY',
            'delivered' => 'DELIVERED',
            'exception' => 'DELIVERY_FAILED',
            default => 'ASSIGNED_TO_RIDER',
        };

        $stage = match ($p->status) {
            'rider_received', 'in_transit' => 'pickedUp',
            'out_for_delivery' => 'outForDelivery',
            'delivered' => 'delivered',
            'exception' => 'failed',
            default => 'accepted',
        };

        $codAmount = (float) ($metadata['cod_amount'] ?? ($p->order?->total_amount ?? 250.00));
        $isCod = (bool) ($metadata['is_cod'] ?? ($codAmount > 0));

        return [
            'id' => $p->parcel_number,
            'db_id' => $p->id,
            'parcel_number' => $p->parcel_number,
            'tracking_code' => $p->parcel_number,
            'tracking_number' => $p->shipment?->tracking_number ?? $p->parcel_number,
            'barcode' => $barcode,
            'store' => 'Lumora Express Hub',
            'pickup' => $p->sort_bin ? "Sorting Bin {$p->sort_bin}" : 'Central Sorting Hub',
            'dropoff' => $dropoff,
            'address' => $dropoff,
            'fee' => '₱150.00',
            'distance' => $metadata['distance'] ?? '3.8 km',
            'eta' => $metadata['eta'] ?? '25 min',
            'status' => $p->status,
            'status_code' => $statusCode,
            'stage' => $stage,
            'recipient_name' => $p->recipient_name,
            'recipient_phone' => $p->recipient_phone ?? '09170000000',
            'destination_area' => $areaName,
            'area' => $areaName,
            'landmark' => $metadata['landmark'] ?? ($p->address_line ?? $areaName),
            'cod_amount' => $codAmount,
            'is_cod' => $isCod,
            'stop_order' => (int) ($metadata['stop_order'] ?? 1),
            'failure_reason' => $metadata['failure_reason'] ?? null,
            'failure_notes' => $metadata['failure_notes'] ?? null,
            'pod_photo_path' => $metadata['proof_url'] ?? null,
            'rider_id' => $p->assigned_rider_id,
            'sort_bin' => $p->sort_bin ?? '',
            'updated_at' => $p->updated_at?->toIso8601String(),
        ];
    }
}

