<?php

namespace App\Http\Controllers;

use App\Models\CommissionTransaction;
use App\Models\Shipment;
use App\Models\Parcel;
use App\Models\RiderProfile;
use App\Models\DestinationArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $branch = $request->user()?->getBranchName();
        $from = $request->date('from')?->startOfDay() ?: now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?: now()->endOfDay();

        $shipmentQuery = Shipment::whereBetween('created_at', [$from, $to]);
        if ($branch) {
            $shipmentQuery->where(function ($q) use ($branch) {
                $q->where('destination_city', 'like', "%{$branch}%")
                  ->orWhereHas('parcels', fn ($pq) => $pq->forBranch($branch));
            });
        }
        $statusTotals = $shipmentQuery->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        $parcelQuery = Parcel::forBranch($branch)->whereBetween('created_at', [$from, $to]);
        $assignedCount = (clone $parcelQuery)->where('status', 'assigned')->count();

        $areas = DestinationArea::where('is_active', true)->forBranch($branch)->pluck('id');
        $riderQuery = RiderProfile::with('user');
        if ($branch && $areas->isNotEmpty()) {
            $riderQuery->whereHas('areas', fn ($aq) => $aq->whereIn('destination_areas.id', $areas));
        }
        $riderTotals = $riderQuery->withCount(['parcels as assigned_count' => fn ($q) => $q->forBranch($branch)->whereBetween('assigned_at', [$from, $to])])
            ->withSum(['commissions as commission_total' => fn ($q) => $q->whereBetween('created_at', [$from, $to])], 'amount')->get();

        $commissionQuery = CommissionTransaction::whereBetween('created_at', [$from, $to]);
        if ($branch) {
            $commissionQuery->whereHas('parcel', fn ($pq) => $pq->forBranch($branch));
        }
        $commissionTotals = $commissionQuery->select('status', DB::raw('sum(amount) as total'))->groupBy('status')->pluck('total', 'status');

        return view('reports.index', compact('from', 'to', 'statusTotals', 'assignedCount', 'riderTotals', 'commissionTotals', 'branch'));
    }
}
