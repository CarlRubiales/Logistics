<?php

namespace App\Http\Controllers;

use App\Models\DestinationArea;
use App\Models\Parcel;
use App\Models\RiderAreaAssignment;
use App\Models\RiderProfile;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Headquarters Overview Dashboard
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->isMasterAdmin(), 403, 'Access denied. Master Logistics Administrator privileges required.');

        // Timezone: Philippine Time (Asia/Manila, UTC+8)
        $tz = 'Asia/Manila';
        $todayPht = Carbon::now($tz)->toDateString();
        $selectedDateStr = $request->input('date', $todayPht);

        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $selectedDateStr, $tz);
        } catch (\Throwable $e) {
            $selectedDate = Carbon::now($tz);
            $selectedDateStr = $todayPht;
        }

        $isToday = $selectedDate->toDateString() === $todayPht;
        $formattedDate = $selectedDate->format('F d, Y');

        // 1. Overall System Metrics for the selected date
        $totalParcels = Parcel::forDate($selectedDate)->count();
        $totalDelivered = Parcel::forDate($selectedDate)->where('status', 'delivered')->count();
        $totalInTransit = Parcel::forDate($selectedDate)->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery'])->count();
        $totalExceptions = Parcel::forDate($selectedDate)->whereIn('status', ['exception', 'failed', 'cancelled'])->count();
        $totalRiders = RiderProfile::where('registration_status', 'approved')->count();

        // 2. Branch Breakdown for the selected date
        $knownBranches = DestinationArea::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch')
            ->merge(Parcel::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch'))
            ->merge(User::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch'))
            ->unique()
            ->values();

        if ($knownBranches->isEmpty()) {
            $knownBranches = collect(['Pila', 'Santa Cruz']);
        }

        $hubSummaries = $knownBranches->map(function ($branchName) use ($selectedDate) {
            $parcels = Parcel::forBranch($branchName)->forDate($selectedDate);
            $areas = DestinationArea::where('is_active', true)->forBranch($branchName);
            $areaIds = $areas->pluck('id');

            $ridersCount = RiderProfile::where('registration_status', 'approved')
                ->where(function ($q) use ($areaIds, $branchName) {
                    if ($areaIds->isNotEmpty()) {
                        $q->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areaIds));
                    }
                    $q->orWhereHas('user', fn ($uq) => $uq->where('branch', $branchName));
                })
                ->count();

            $counts = (clone $parcels)->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $account = User::where('branch', $branchName)->first();

            return [
                'branch' => $branchName,
                'account' => $account,
                'total_parcels' => (clone $parcels)->count(),
                'received' => $counts['received'] ?? 0,
                'scanned' => $counts['scanned'] ?? 0,
                'sorted' => $counts['sorted'] ?? 0,
                'in_transit' => ($counts['assigned'] ?? 0) + ($counts['rider_received'] ?? 0) + ($counts['in_transit'] ?? 0) + ($counts['out_for_delivery'] ?? 0),
                'delivered' => $counts['delivered'] ?? 0,
                'riders_count' => $ridersCount,
                'areas_count' => $areas->count(),
            ];
        });

        // 3. Logistics Accounts
        $logisticsUsers = User::whereIn('role', ['super_admin', 'admin', 'operations_manager', 'warehouse_staff', 'support'])
            ->orderByRaw("FIELD(role, 'super_admin', 'admin', 'operations_manager', 'warehouse_staff', 'support')")
            ->latest()
            ->get();

        return view('admin.dashboard', compact(
            'totalParcels',
            'totalDelivered',
            'totalInTransit',
            'totalExceptions',
            'totalRiders',
            'hubSummaries',
            'logisticsUsers',
            'selectedDateStr',
            'formattedDate',
            'isToday'
        ));
    }

    /**
     * Dedicated Branches Dashboard:
     * Contains who are the riders for each branch (for admin only)
     */
    public function branches(Request $request): View
    {
        abort_unless($request->user()->isMasterAdmin(), 403, 'Access denied. Master Administrator privileges required.');

        $tz = 'Asia/Manila';
        $todayPht = Carbon::now($tz)->toDateString();
        $selectedDateStr = $request->input('date', $todayPht);

        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $selectedDateStr, $tz);
        } catch (\Throwable $e) {
            $selectedDate = Carbon::now($tz);
            $selectedDateStr = $todayPht;
        }

        $isToday = $selectedDate->toDateString() === $todayPht;
        $formattedDate = $selectedDate->format('F d, Y');

        $knownBranches = DestinationArea::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch')
            ->merge(Parcel::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch'))
            ->merge(User::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch'))
            ->unique()
            ->values();

        if ($knownBranches->isEmpty()) {
            $knownBranches = collect(['Pila', 'Santa Cruz']);
        }

        // Build rich branch details including assigned riders for the selected date
        $branchData = $knownBranches->map(function ($branchName) use ($selectedDate) {
            $account = User::where('branch', $branchName)->first();
            $areas = DestinationArea::where('branch', $branchName)->orderBy('name')->get();
            $areaIds = $areas->pluck('id');

            // Find all riders assigned to this branch
            $riders = RiderProfile::with(['user', 'areas'])
                ->where(function ($q) use ($areaIds, $branchName) {
                    if ($areaIds->isNotEmpty()) {
                        $q->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areaIds));
                    }
                    $q->orWhereHas('user', fn ($uq) => $uq->where('branch', $branchName));
                })
                ->get()
                ->map(function ($rider) use ($selectedDate) {
                    $activeParcels = $rider->parcels()
                        ->forDate($selectedDate)
                        ->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery'])
                        ->count();

                    return [
                        'profile' => $rider,
                        'name' => $rider->user->name,
                        'email' => $rider->user->email,
                        'phone' => $rider->phone ?: 'No phone',
                        'vehicle_type' => $rider->vehicle_type ?: 'Motorcycle',
                        'license_number' => $rider->license_number,
                        'has_license' => ! empty($rider->license_photo_path),
                        'has_or_cr' => ! empty($rider->or_cr_photo_path),
                        'availability' => $rider->availability,
                        'registration_status' => $rider->registration_status,
                        'active_parcels' => $activeParcels,
                        'assigned_areas' => $rider->areas->pluck('name')->implode(', '),
                    ];
                });

            // Parcels for this branch on the selected date
            $parcels = Parcel::forBranch($branchName)->forDate($selectedDate);
            $totalParcels = (clone $parcels)->count();
            $inTransit = (clone $parcels)->whereIn('status', ['assigned', 'rider_received', 'in_transit', 'out_for_delivery'])->count();
            $delivered = (clone $parcels)->where('status', 'delivered')->count();

            return [
                'name' => $branchName,
                'account' => $account,
                'areas' => $areas,
                'riders' => $riders,
                'total_riders' => $riders->count(),
                'online_riders' => $riders->whereIn('availability', ['online', 'available'])->count(),
                'total_parcels' => $totalParcels,
                'in_transit' => $inTransit,
                'delivered' => $delivered,
            ];
        });

        // Unassigned riders (riders not associated with any branch yet)
        $allAssignedRiderIds = RiderAreaAssignment::pluck('rider_profile_id')->unique();
        $unassignedRiders = RiderProfile::with(['user', 'areas'])
            ->whereNotIn('id', $allAssignedRiderIds)
            ->whereHas('user', fn ($uq) => $uq->whereNull('branch')->orWhere('branch', ''))
            ->get();

        $allAreas = DestinationArea::where('is_active', true)->orderBy('branch')->orderBy('name')->get();

        return view('admin.branches', compact('branchData', 'unassignedRiders', 'allAreas', 'selectedDateStr', 'formattedDate', 'isToday'));
    }

    /**
     * Dedicated Riders Dashboard for Admin:
     * Handles all riders, organized into sections for each branch,
     * showing names and submitted requirements/documents (License, OR/CR).
     */
    public function riders(Request $request): View
    {
        abort_unless($request->user()->isMasterAdmin(), 403, 'Access denied. Master Administrator privileges required.');

        $search = $request->input('search');
        $status = $request->input('status');

        $knownBranches = DestinationArea::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch')
            ->merge(Parcel::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch'))
            ->merge(User::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch'))
            ->unique()
            ->values();

        if ($knownBranches->isEmpty()) {
            $knownBranches = collect(['Pila', 'Santa Cruz']);
        }

        // Global stats
        $counts = [
            'total' => RiderProfile::count(),
            'approved' => RiderProfile::where('registration_status', 'approved')->count(),
            'pending' => RiderProfile::where('registration_status', 'pending')->count(),
            'online' => RiderProfile::whereIn('availability', ['online', 'available'])->count(),
        ];

        // Group riders by branch
        $branchSections = $knownBranches->map(function ($branchName) use ($search, $status) {
            $areas = DestinationArea::where('branch', $branchName)->get();
            $areaIds = $areas->pluck('id');

            $query = RiderProfile::with(['user', 'areas'])
                ->where(function ($q) use ($areaIds, $branchName) {
                    if ($areaIds->isNotEmpty()) {
                        $q->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areaIds));
                    }
                    $q->orWhereHas('user', fn ($uq) => $uq->where('branch', $branchName));
                });

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('vehicle_type', 'like', "%{$search}%")
                      ->orWhere('license_number', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $query->where('registration_status', $status);
            }

            $riders = $query->latest()->get();

            return [
                'branch' => $branchName,
                'areas' => $areas,
                'riders' => $riders,
                'count' => $riders->count(),
                'approved_count' => $riders->where('registration_status', 'approved')->count(),
                'pending_count' => $riders->where('registration_status', 'pending')->count(),
            ];
        });

        // Unassigned riders section
        $assignedIds = RiderAreaAssignment::pluck('rider_profile_id')->unique();
        $unassignedQuery = RiderProfile::with(['user', 'areas'])
            ->whereNotIn('id', $assignedIds)
            ->whereHas('user', fn ($uq) => $uq->whereNull('branch')->orWhere('branch', ''));

        if ($search) {
            $unassignedQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('vehicle_type', 'like', "%{$search}%");
            });
        }
        if ($status) {
            $unassignedQuery->where('registration_status', $status);
        }

        $unassignedRiders = $unassignedQuery->latest()->get();

        $allAreas = DestinationArea::where('is_active', true)->orderBy('branch')->orderBy('name')->get();

        return view('admin.riders', compact('branchSections', 'unassignedRiders', 'counts', 'allAreas', 'search', 'status'));
    }

    /**
     * Dedicated Dashboard for Create Account (Branch Provisioning)
     */
    public function accounts(Request $request): View
    {
        abort_unless($request->user()->isMasterAdmin(), 403, 'Access denied. Master Administrator privileges required.');

        $branchAccounts = User::whereIn('role', ['super_admin', 'admin', 'operations_manager', 'warehouse_staff', 'support'])
            ->orderByRaw("FIELD(role, 'super_admin', 'admin', 'operations_manager', 'warehouse_staff', 'support')")
            ->latest()
            ->get();

        $knownBranches = DestinationArea::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch')
            ->merge(User::whereNotNull('branch')->where('branch', '!=', '')->distinct()->pluck('branch'))
            ->unique()
            ->values();

        return view('admin.accounts', compact('branchAccounts', 'knownBranches'));
    }

    /**
     * Store a new branch account
     */
    public function storeUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isMasterAdmin(), 403, 'Access denied.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'branch' => ['required', 'string', 'max:80'],
            'role' => ['required', 'string', Rule::in(['admin', 'operations_manager', 'warehouse_staff', 'support'])],
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'branch' => trim($data['branch']),
            'role' => $data['role'],
        ]);

        // Auto-create initial destination area for new branch if non-existent
        $branchClean = trim($data['branch']);
        $existingArea = DestinationArea::where('branch', $branchClean)->first();
        if (! $existingArea) {
            $storeId = DB::table('stores')->where('is_active', true)->value('id') ?? 1;
            $autoCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $branchClean), 0, 4)) . '-' . strtoupper(substr(uniqid(), -3));
            DestinationArea::create([
                'store_id' => $storeId,
                'name' => "{$branchClean} Central Hub",
                'branch' => $branchClean,
                'province' => 'Laguna',
                'code' => $autoCode,
                'cities' => [$branchClean],
                'is_active' => true,
            ]);
        }

        return redirect()->route('admin.accounts')->with('success', "New logistics account for '{$user->name}' ({$user->branch} Hub) created successfully! Login Email: {$user->email}");
    }

    /**
     * Delete a branch account
     */
    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isMasterAdmin(), 403, 'Access denied.');

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'You cannot delete your own master admin account.']);
        }

        $userName = $user->name;
        $branch = $user->branch;
        $user->delete();

        return redirect()->route('admin.accounts')->with('success', "Logistics user account '{$userName}' ({$branch}) has been deleted.");
    }

    /**
     * Reset password for a branch account
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isMasterAdmin(), 403, 'Access denied.');

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.accounts')->with('success', "Password for {$user->name} ({$user->email}) updated successfully.");
    }
}
