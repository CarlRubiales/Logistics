<?php

namespace App\Http\Controllers;

use App\Models\CommissionTransaction;
use App\Models\Shipment;
use App\Models\Parcel;
use App\Models\RiderProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?: now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?: now()->endOfDay();
        $statusTotals = Shipment::whereBetween('created_at', [$from, $to])->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $assignedCount = Parcel::whereBetween('created_at', [$from, $to])->where('status', 'assigned')->count();
        $riderTotals = RiderProfile::with('user')->withCount(['parcels as assigned_count' => fn ($q) => $q->whereBetween('assigned_at', [$from, $to])])->withSum(['commissions as commission_total' => fn ($q) => $q->whereBetween('created_at', [$from, $to])], 'amount')->get();
        $commissionTotals = CommissionTransaction::whereBetween('created_at', [$from, $to])->select('status', DB::raw('sum(amount) as total'))->groupBy('status')->pluck('total', 'status');
        return view('reports.index', compact('from', 'to', 'statusTotals', 'assignedCount', 'riderTotals', 'commissionTotals'));
    }
}
