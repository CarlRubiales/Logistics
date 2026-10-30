<?php

namespace App\Http\Controllers;

use App\Models\CommissionTransaction;
use App\Models\DestinationArea;
use App\Models\ErpMessage;
use App\Models\Hub;
use App\Models\Parcel;
use App\Models\ParcelScan;
use App\Models\RiderAreaAssignment;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RiderController extends Controller
{
    public function registerForm() { return view('riders.register'); }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:40'],
            'vehicle_type' => ['required', 'string', Rule::in(['Motorcycle', 'Tricycle', 'motorcycle', 'tricycle'])],
            'license_number' => ['nullable', 'string', 'max:80'],
            'drivers_license' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:10240'],
            'or_cr' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:10240'],
        ]);

        $vehicleType = match (strtolower(trim($data['vehicle_type']))) {
            'tricycle' => 'Tricycle',
            default => 'Motorcycle',
        };

        $licensePath = null;
        if ($request->hasFile('drivers_license')) {
            $licensePath = $request->file('drivers_license')->store('rider_documents', 'public');
        }

        $orCrPath = null;
        if ($request->hasFile('or_cr')) {
            $orCrPath = $request->file('or_cr')->store('rider_documents', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'rider',
        ]);

        $profile = RiderProfile::create([
            'user_id' => $user->id,
            'phone' => $data['phone'],
            'vehicle_type' => $vehicleType,
            'license_number' => $data['license_number'] ?? null,
            'license_photo_path' => $licensePath,
            'or_cr_photo_path' => $orCrPath,
            'registration_status' => 'approved',
            'approved_at' => now(),
            'availability' => 'offline',
        ]);

        return redirect()->route('login')->with('status', 'Registration complete. You may now log in to access your rider portal.');
    }

    public function document(RiderProfile $rider, string $type)
    {
        $path = match ($type) {
            'license', 'drivers_license' => $rider->license_photo_path,
            'or_cr', 'orcr' => $rider->or_cr_photo_path,
            default => null,
        };

        if ($path && str_starts_with($path, 'http')) {
            return redirect()->away($path);
        }

        abort_unless($path && Storage::disk('public')->exists($path), 404, 'Document file not found.');

        return Storage::disk('public')->response($path);
    }

    public function index(Request $request)
    {
        if ($request->user()?->isMasterAdmin()) {
            return redirect()->route('admin.riders');
        }

        $branch = $request->user()?->getBranchName();
        $areas = DestinationArea::where('is_active', true)->forBranch($branch)->orderBy('name')->get();
        $areaIds = $areas->pluck('id');

        $query = RiderProfile::with(['user', 'areas'])->latest();

        if ($branch && $areaIds->isNotEmpty()) {
            $query->where(function ($rq) use ($areaIds) {
                $rq->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areaIds))
                   ->orDoesntHave('areas');
            });
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
                })->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('vehicle_type', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('registration_status', $request->string('status'));
        }

        if ($request->filled('area_id')) {
            $areaId = $request->integer('area_id');
            $query->whereHas('areas', function ($q) use ($areaId) {
                $q->where('destination_areas.id', $areaId);
            });
        }

        $riders = $query->paginate(20)->withQueryString();

        $baseCount = RiderProfile::query();
        if ($branch && $areaIds->isNotEmpty()) {
            $baseCount->where(function ($rq) use ($areaIds) {
                $rq->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areaIds))
                   ->orDoesntHave('areas');
            });
        }

        $counts = [
            'total' => (clone $baseCount)->count(),
            'approved' => (clone $baseCount)->where('registration_status', 'approved')->count(),
            'online' => (clone $baseCount)->where('registration_status', 'approved')->whereIn('availability', ['online', 'available'])->count(),
            'pending' => (clone $baseCount)->where('registration_status', 'pending')->count(),
        ];

        return view('riders.index', compact('riders', 'areas', 'counts', 'branch'));
    }

    public function approve(Request $request, RiderProfile $rider)
    {
        $status = $request->input('status');
        if (! in_array($status, ['approved', 'disapproved', 'pending'])) {
            $status = $request->boolean('approved') ? 'approved' : 'disapproved';
        }

        $reason = trim($request->input('reason') ?? $request->input('rejection_reason') ?? $request->input('note') ?? '');

        $rider->update([
            'registration_status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
            'approved_by' => $status === 'approved' ? $request->user()?->id : null,
            'registration_note' => $status === 'disapproved' ? ($reason ?: 'Document requirements not met.') : null,
        ]);

        $user = $rider->user;
        $hubName = $rider->hub?->name ?? ($user?->branch ? "{$user->branch} Hub" : 'Lumora Logistics Hub');
        $branch = $user?->branch ?? ($rider->hub?->branch ?? 'Local Branch');

        if ($user && ! empty($user->email)) {
            if ($status === 'approved') {
                try {
                    Mail::send('emails.rider_approved', [
                        'name' => $user->name,
                        'email' => $user->email,
                        'hubName' => $hubName,
                        'branch' => $branch,
                        'vehicle' => $rider->vehicle_type ?? 'Motorcycle',
                    ], function ($m) use ($user, $hubName) {
                        $m->to($user->email)
                            ->subject("✓ Application Approved - Welcome to {$hubName} (Lumora Logistics)");
                    });
                } catch (\Throwable $e) {
                    Log::error("Failed to send approval email to {$user->email}: " . $e->getMessage());
                }
            } elseif ($status === 'disapproved') {
                try {
                    Mail::send('emails.rider_rejected', [
                        'name' => $user->name,
                        'email' => $user->email,
                        'hubName' => $hubName,
                        'branch' => $branch,
                        'reason' => $reason ?: 'Document verification criteria were not fulfilled. Please contact your hub coordinator.',
                    ], function ($m) use ($user, $hubName) {
                        $m->to($user->email)
                            ->subject("Notice Regarding Your Courier Application - {$hubName}");
                    });
                } catch (\Throwable $e) {
                    Log::error("Failed to send rejection email to {$user->email}: " . $e->getMessage());
                }
            }
        }

        $actionText = $status === 'approved'
            ? 'approved and notified via Gmail'
            : ($status === 'disapproved' ? 'rejected and notified with reason via Gmail' : 'status updated');

        return back()->with('success', "Rider {$rider->user->name} has been {$actionText}.");
    }

    public function assignArea(Request $request, RiderProfile $rider)
    {
        $data = $request->validate([
            'destination_area_id' => ['required', 'exists:destination_areas,id'],
        ]);
        RiderAreaAssignment::updateOrCreate(
            ['rider_profile_id' => $rider->id, 'destination_area_id' => $data['destination_area_id']],
            ['is_active' => true, 'is_primary' => $rider->areaAssignments()->doesntExist()]
        );
        return back()->with('success', "Delivery area assigned to {$rider->user->name}.");
    }

    public function removeArea(RiderProfile $rider, DestinationArea $area)
    {
        RiderAreaAssignment::where('rider_profile_id', $rider->id)
            ->where('destination_area_id', $area->id)
            ->delete();

        return back()->with('success', "Area '{$area->name}' unassigned from {$rider->user->name}.");
    }

    public function dashboard(Request $request)
    {
        $rider = $request->user()->riderProfile?->load('areas');
        abort_unless($rider && $rider->registration_status === 'approved', 403, 'Your rider account is awaiting administrator approval.');

        $query = $rider->parcels()->with(['area', 'shipment', 'scans'])->latest('assigned_at');

        if ($request->filled('status')) {
            $filter = $request->string('status');
            if ($filter === 'active') {
                $query->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery']);
            } elseif ($filter === 'delivered') {
                $query->where('status', 'delivered');
            } elseif ($filter === 'exception') {
                $query->whereIn('status', ['exception', 'failed']);
            } else {
                $query->where('status', $filter);
            }
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('parcel_number', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%")
                    ->orWhere('recipient_name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%");
            });
        }

        $parcels = $query->paginate(15)->withQueryString();

        $counts = [
            'total' => $rider->parcels()->count(),
            'assigned' => $rider->parcels()->where('status', 'assigned')->count(),
            'in_transit' => $rider->parcels()->whereIn('status', ['rider_received', 'in_transit', 'out_for_delivery'])->count(),
            'delivered' => $rider->parcels()->where('status', 'delivered')->count(),
            'exception' => $rider->parcels()->whereIn('status', ['exception', 'failed'])->count(),
        ];

        $todayStart = now()->startOfDay();
        $todayEarnings = CommissionTransaction::where('rider_profile_id', $rider->id)
            ->where('earned_at', '>=', $todayStart)
            ->sum('amount');

        $totalBalance = CommissionTransaction::where('rider_profile_id', $rider->id)
            ->where('status', 'pending')
            ->sum('amount');

        $recentMessages = ErpMessage::with(['sender', 'recipient', 'parcel'])
            ->where(function ($q) use ($request) {
                $q->where('sender_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            })
            ->latest()
            ->take(6)
            ->get();

        $unreadCount = ErpMessage::where('recipient_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return view('riders.dashboard', compact(
            'rider',
            'parcels',
            'counts',
            'todayEarnings',
            'totalBalance',
            'recentMessages',
            'unreadCount'
        ));
    }

    public function scanBarcode(Request $request)
    {
        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'in:lookup,accept,in_transit,out_for_delivery,delivered'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $code = trim($data['barcode']);
        $parcel = Parcel::where('barcode', $code)
            ->orWhere('parcel_number', $code)
            ->orWhere('id', is_numeric($code) ? $code : -1)
            ->orWhereHas('shipment', function ($q) use ($code) {
                $q->where('tracking_number', $code);
            })
            ->first();

        if (! $parcel) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => "No parcel found matching barcode '{$code}'."], 404);
            }
            return back()->withErrors(['barcode' => "No parcel found matching barcode '{$code}'."]);
        }

        $rider = $request->user()->riderProfile;
        abort_unless($rider && $rider->registration_status === 'approved', 403);

        // If parcel is unassigned, assign to this rider
        if (! $parcel->assigned_rider_id) {
            $parcel->update([
                'assigned_rider_id' => $rider->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);
        } elseif ($parcel->assigned_rider_id !== $rider->id) {
            $msg = "Parcel {$parcel->parcel_number} is currently assigned to another courier.";
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 403);
            }
            return back()->withErrors(['barcode' => $msg]);
        }

        $action = $data['action'] ?? 'lookup';
        $newStatus = match ($action) {
            'accept', 'in_transit' => 'in_transit',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            default => null,
        };

        if ($newStatus) {
            $parcel->update([
                'status' => $newStatus,
                'rider_received_at' => in_array($newStatus, ['in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at ? now() : $parcel->rider_received_at,
            ]);

            ParcelScan::create([
                'parcel_id' => $parcel->id,
                'user_id' => $request->user()->id,
                'scan_type' => $newStatus,
                'barcode' => $parcel->barcode ?: $code,
                'result' => 'success',
                'note' => $data['notes'] ?: "Barcode scanned ({$action}) by rider {$request->user()->name}",
                'scanned_at' => now(),
            ]);

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

            // Automatically sync status to shipment & tracking events
            app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

            $successMsg = "Parcel {$parcel->parcel_number} scanned successfully! Status updated to " . ucwords(str_replace('_', ' ', $newStatus)) . ".";
        } else {
            // Pure barcode verification scan
            ParcelScan::create([
                'parcel_id' => $parcel->id,
                'user_id' => $request->user()->id,
                'scan_type' => 'scan_check',
                'barcode' => $parcel->barcode ?: $code,
                'result' => 'success',
                'note' => "Barcode verified by rider {$request->user()->name}",
                'scanned_at' => now(),
            ]);
            $successMsg = "Verified parcel {$parcel->parcel_number} for {$parcel->recipient_name} ({$parcel->address_line}). Current status: " . ucwords(str_replace('_', ' ', $parcel->status)) . ".";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMsg,
                'parcel' => $parcel->fresh(['area', 'shipment']),
            ]);
        }

        return back()->with('success', $successMsg);
    }

    public function updateStatus(Request $request, Parcel $parcel)
    {
        $rider = $request->user()->riderProfile;
        abort_unless($rider && $parcel->assigned_rider_id === $rider->id, 403, 'Unauthorized.');

        $data = $request->validate([
            'status' => ['required', 'string', 'in:in_transit,out_for_delivery,delivered,exception'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $status = $data['status'];
        $note = $data['note'] ?? ("Status updated to " . str_replace('_', ' ', $status) . " by rider " . $request->user()->name);

        $parcel->update([
            'status' => $status,
            'rider_received_at' => in_array($status, ['in_transit', 'out_for_delivery']) && ! $parcel->rider_received_at ? now() : $parcel->rider_received_at,
        ]);

        ParcelScan::create([
            'parcel_id' => $parcel->id,
            'user_id' => $request->user()->id,
            'scan_type' => $status,
            'barcode' => $parcel->barcode,
            'result' => 'success',
            'note' => $note,
            'scanned_at' => now(),
        ]);

        // Automatically sync status to shipment & tracking events
        app(\App\Services\ParcelShipmentSyncService::class)->sync($parcel->fresh());

        if ($status === 'delivered') {
            CommissionTransaction::firstOrCreate(
                [
                    'rider_profile_id' => $rider->id,
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

        return back()->with('success', "Delivery status for {$parcel->parcel_number} updated to " . ucwords(str_replace('_', ' ', $status)) . ".");
    }

    public function toggleAvailability(Request $request)
    {
        $rider = $request->user()->riderProfile;
        abort_unless($rider, 404);

        $newAvailability = $rider->availability === 'online' ? 'offline' : 'online';
        $rider->update(['availability' => $newAvailability]);

        return back()->with('success', "Availability status updated to " . strtoupper($newAvailability) . ".");
    }
}
