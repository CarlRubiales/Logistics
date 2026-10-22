<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SellerApprovedMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{
    public function users()
    {
        $users = DB::table('users')->orderByDesc('created_at')->get();

        return view('admin.users', compact('users'));
    }

    public function applications()
    {
        $applications = DB::table('seller_applications as sa')
            ->join('users as u', 'u.id', '=', 'sa.user_id')
            ->select('sa.*', 'u.name', 'u.email')
            ->orderByDesc('sa.created_at')
            ->get();

        return view('admin.applications', compact('applications'));
    }

    public function index()
    {
        $totalSales = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.payment_status', 'paid')
            ->sum(DB::raw('oi.price * oi.quantity'));

        $totalOrders = DB::table('orders')->count();
        $totalProducts = DB::table('products')->count();
        $totalBuyers = DB::table('users')->where('role', 'buyer')->count();

        $applications = DB::table('seller_applications as sa')
            ->join('users as u', 'u.id', '=', 'sa.user_id')
            ->where('sa.status', 'pending')
            ->select('sa.*', 'u.name', 'u.email')
            ->orderByDesc('sa.created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('totalSales', 'totalOrders', 'totalProducts', 'totalBuyers', 'applications'));
    }

    public function handleApplication($id, $action)
    {
        $newStatus = match ($action) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'archive' => 'archived',
            default => null,
        };

        if (! $newStatus) {
            abort(404);
        }

        DB::table('seller_applications')->where('id', $id)->update(['status' => $newStatus]);

        if ($newStatus === 'approved') {
            $app = DB::table('seller_applications')->find($id);
            $seller = DB::table('users')->find($app->user_id);

            DB::table('users')->where('id', $app->user_id)->update(['role' => 'seller']);

            if ($seller) {
                Mail::to($seller->email)->send(new SellerApprovedMail($seller->name, $app->business_name));
            }
        }

        session()->flash('flash_success', 'Application '.$newStatus.'.');

        return redirect()->route('admin.dashboard');
    }

    public function handleUserStatus($id, $action)
    {
        $newStatus = match ($action) {
            'activate' => 'active',
            'suspend' => 'suspended',
            'deactivate' => 'deactivated',
            default => null,
        };

        if (! $newStatus) {
            abort(404);
        }

        DB::table('users')->where('id', $id)->update(['status' => $newStatus]);

        session()->flash('flash_success', 'User account '.$newStatus.'.');

        return redirect()->route('admin.users');
    }

    /**
     * Compliance dashboard: lists every product with its seller and the seller's
     * registered category (from their approved seller_applications row, if any),
     * so admin can spot mismatches and manually flag prohibited items.
     *
     * Note: if a seller somehow has more than one 'approved' application row,
     * this join may show one of them arbitrarily — fine for now since sellers
     * normally only get approved once.
     */
    public function compliance()
    {
        $products = DB::table('products as p')
            ->join('users as u', 'u.id', '=', 'p.seller_id')
            ->leftJoin('seller_applications as sa', function ($join) {
                $join->on('sa.user_id', '=', 'u.id')
                    ->where('sa.status', '=', 'approved');
            })
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.category as product_category',
                'p.status as product_status',
                'u.id as seller_id',
                'u.name as seller_name',
                'u.status as seller_status',
                'sa.category as registered_category'
            )
            ->orderBy('u.name')
            ->orderByDesc('p.created_at')
            ->get();

        // Warning history per seller, newest first, grouped by seller_id
        $warningsBySeller = DB::table('seller_warnings')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('seller_id');

        return view('admin.compliance', compact('products', 'warningsBySeller'));
    }

    public function flagProduct($id)
    {
        DB::table('products')->where('id', $id)->update([
            'status' => 'flagged',
            'updated_at' => now(),
        ]);

        session()->flash('flash_success', 'Product flagged as prohibited and hidden from the storefront.');

        return redirect()->route('admin.compliance');
    }

    public function clearProduct($id)
    {
        DB::table('products')->where('id', $id)->update([
            'status' => 'active',
            'updated_at' => now(),
        ]);

        session()->flash('flash_success', 'Product cleared and set back to active.');

        return redirect()->route('admin.compliance');
    }

    public function warnSeller(Request $request, $id)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'product_id' => ['nullable', 'integer'],
        ]);

        DB::table('seller_warnings')->insert([
            'seller_id' => $id,
            'product_id' => $request->product_id,
            'reason' => $request->reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $seller = DB::table('users')->find($id);
        session()->flash('flash_success', 'Warning logged for '.($seller->name ?? 'this seller').'.');

        return redirect()->route('admin.compliance');
    }

    /**
     * Complaints & Disputes: lists buyer-filed complaints against a product/seller,
     * with supporting evidence images, so admin can review and coordinate a resolution.
     * (Buyer-facing filing page not built yet — rows are inserted manually for now.)
     */
    public function complaints()
    {
        $complaints = DB::table('complaints as c')
            ->join('users as buyer', 'buyer.id', '=', 'c.buyer_id')
            ->join('users as seller', 'seller.id', '=', 'c.seller_id')
            ->join('products as p', 'p.id', '=', 'c.product_id')
            ->select(
                'c.*',
                'buyer.name as buyer_name',
                'buyer.email as buyer_email',
                'seller.name as seller_name',
                'seller.email as seller_email',
                'p.name as product_name'
            )
            ->orderByDesc('c.created_at')
            ->get();

        // Evidence images grouped by complaint_id
        $evidenceByComplaint = DB::table('complaint_evidence')
            ->orderBy('id')
            ->get()
            ->groupBy('complaint_id');

        return view('admin.complaints', compact('complaints', 'evidenceByComplaint'));
    }

    public function resolveComplaint(Request $request, $id)
    {
        $request->validate(['admin_note' => ['required', 'string', 'max:1000']]);

        DB::table('complaints')->where('id', $id)->update([
            'status' => 'resolved',
            'admin_note' => $request->admin_note,
            'resolved_at' => now(),
            'updated_at' => now(),
        ]);

        session()->flash('flash_success', 'Complaint marked as resolved.');

        return redirect()->route('admin.complaints');
    }

    public function dismissComplaint(Request $request, $id)
    {
        $request->validate(['admin_note' => ['required', 'string', 'max:1000']]);

        DB::table('complaints')->where('id', $id)->update([
            'status' => 'dismissed',
            'admin_note' => $request->admin_note,
            'resolved_at' => now(),
            'updated_at' => now(),
        ]);

        session()->flash('flash_success', 'Complaint dismissed.');

        return redirect()->route('admin.complaints');
    }

    /**
     * Commission dashboard: platform takes 10% of every paid sale.
     * Shows total commission earned platform-wide and a breakdown per seller.
     */
    public function commission()
    {
        $commissionRate = 0.10;

        $sellerSales = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('users as u', 'u.id', '=', 'p.seller_id')
            ->where('o.payment_status', 'paid')
            ->select('u.id as seller_id', 'u.name as seller_name', DB::raw('SUM(oi.price * oi.quantity) as gross_sales'))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('gross_sales')
            ->get()
            ->map(function ($row) use ($commissionRate) {
                $row->commission = round($row->gross_sales * $commissionRate, 2);
                $row->net_earnings = round($row->gross_sales - $row->commission, 2);

                return $row;
            });

        $totalGrossSales = $sellerSales->sum('gross_sales');
        $totalCommission = $sellerSales->sum('commission');

        return view('admin.commission', compact('sellerSales', 'totalGrossSales', 'totalCommission', 'commissionRate'));
    }

    /**
     * Turns the 'range' or 'start_date'/'end_date' query params into a
     * concrete [start, end] Carbon date pair. Custom dates win if both given.
     */
    private function resolveDateRange(Request $request): array
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $days = (int) $request->input('range', 7);
            if ($days <= 0) {
                $days = 7;
            }
            $end = now()->endOfDay();
            $start = now()->subDays($days - 1)->startOfDay();
        }

        return [$start, $end];
    }

    public function salesSummaryPdf(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);

        $totalSales = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$start, $end])
            ->sum(DB::raw('oi.price * oi.quantity'));

        $totalOrders = DB::table('orders')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        $topProducts = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('users as u', 'u.id', '=', 'p.seller_id')
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$start, $end])
            ->select(
                'p.name as product_name',
                'u.name as seller_name',
                DB::raw('SUM(oi.quantity) as units_sold'),
                DB::raw('SUM(oi.price * oi.quantity) as revenue')
            )
            ->groupBy('p.id', 'p.name', 'u.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $topSellers = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('users as u', 'u.id', '=', 'p.seller_id')
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$start, $end])
            ->select(
                'u.name as seller_name',
                DB::raw('COUNT(DISTINCT oi.order_id) as orders_count'),
                DB::raw('SUM(oi.price * oi.quantity) as revenue')
            )
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $pdf = Pdf::loadView('admin.reports.sales-summary-pdf', compact(
            'totalSales', 'totalOrders', 'avgOrderValue', 'topProducts', 'topSellers', 'start', 'end'
        ));

        return $pdf->download('sales-summary-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function commissionReportPdf(Request $request)
    {
        [$start, $end] = $this->resolveDateRange($request);
        $commissionRate = 0.10;

        $sellerSales = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('users as u', 'u.id', '=', 'p.seller_id')
            ->where('o.payment_status', 'paid')
            ->whereBetween('o.created_at', [$start, $end])
            ->select('u.id as seller_id', 'u.name as seller_name', DB::raw('SUM(oi.price * oi.quantity) as gross_sales'))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('gross_sales')
            ->get()
            ->map(function ($row) use ($commissionRate) {
                $row->commission = round($row->gross_sales * $commissionRate, 2);
                $row->net_earnings = round($row->gross_sales - $row->commission, 2);

                return $row;
            });

        $totalGrossSales = $sellerSales->sum('gross_sales');
        $totalCommission = $sellerSales->sum('commission');
        $totalNet = $sellerSales->sum('net_earnings');

        $pdf = Pdf::loadView('admin.reports.commission-pdf', compact(
            'sellerSales', 'totalGrossSales', 'totalCommission', 'totalNet', 'commissionRate', 'start', 'end'
        ));

        return $pdf->download('commission-report-'.now()->format('Y-m-d').'.pdf');
    }

    private function ensureVouchersTableExists(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('vouchers')) {
                \Illuminate\Support\Facades\Schema::create('vouchers', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('code', 50)->unique();
                    $table->string('type', 20)->default('percent');
                    $table->decimal('value', 10, 2)->default(0);
                    $table->decimal('min_spend', 10, 2)->default(0);
                    $table->integer('usage_limit')->nullable();
                    $table->integer('used_count')->default(0);
                    $table->date('expires_at')->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Ignore dynamic schema error
        }
    }

    public function vouchers()
    {
        $this->ensureVouchersTableExists();
        $vouchers = DB::table('vouchers')->orderByDesc('created_at')->get();

        return view('admin.vouchers', compact('vouchers'));
    }

    public function storeVoucher(Request $request)
    {
        $this->ensureVouchersTableExists();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:percent,fixed,shipping'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_spend' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $code = strtoupper(trim($validated['code']));

        DB::table('vouchers')->updateOrInsert(
            ['code' => $code],
            [
                'type' => $validated['type'],
                'value' => (float) $validated['value'],
                'min_spend' => (float) ($validated['min_spend'] ?? 0),
                'usage_limit' => $validated['usage_limit'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('flash_success', "Voucher {$code} saved successfully.");
    }

    public function toggleVoucher($id)
    {
        $this->ensureVouchersTableExists();
        $voucher = DB::table('vouchers')->where('id', $id)->first();
        if ($voucher) {
            DB::table('vouchers')->where('id', $id)->update([
                'is_active' => ! $voucher->is_active,
                'updated_at' => now(),
            ]);
        }

        return back()->with('flash_success', 'Voucher status updated.');
    }

    public function destroyVoucher($id)
    {
        $this->ensureVouchersTableExists();
        DB::table('vouchers')->where('id', $id)->delete();

        return back()->with('flash_success', 'Voucher removed.');
    }
}
