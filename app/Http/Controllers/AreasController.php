<?php

namespace App\Http\Controllers;

use App\Models\DestinationArea;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AreasController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()?->isMasterAdmin()) {
            return redirect()->route('admin.branches');
        }

        $branch = $request->user()?->getBranchName();
        $storeId = DB::table('stores')->where('is_active', true)->value('id') ?? DB::table('stores')->value('id');
        $areas = DestinationArea::withCount(['parcels', 'riders'])
            ->where('store_id', $storeId)
            ->forBranch($branch)
            ->orderBy('name')
            ->get();
        return view('areas.index', compact('areas', 'branch'));
    }

    public function store(Request $request)
    {
        $storeId = DB::table('stores')->where('is_active', true)->value('id') ?? DB::table('stores')->value('id');
        abort_unless($storeId, 422, 'Create a store before creating delivery areas.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:100'],
            'cities' => ['nullable', 'string'],
            'postal_codes' => ['nullable', 'string'],
            'coverage_rules' => ['nullable', 'string'],
        ]);

        $province = trim($data['province']);
        $autoCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $province), 0, 4)) . '-' . strtoupper(substr(uniqid(), -4));
        $branch = $request->user()?->getBranchName();

        DestinationArea::create([
            'store_id' => $storeId,
            'name' => $data['name'],
            'branch' => $branch,
            'province' => $province,
            'code' => $autoCode,
            'cities' => collect(explode(',', $data['cities'] ?? ''))->map(fn ($v) => trim($v))->filter()->values(),
            'postal_codes' => collect(explode(',', $data['postal_codes'] ?? ''))->map(fn ($v) => trim($v))->filter()->values(),
            'coverage_rules' => $data['coverage_rules'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', "Delivery area '{$data['name']}' ({$province}) created successfully.");
    }

    public function update(Request $request, DestinationArea $area)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:100'],
            'cities' => ['nullable', 'string'],
            'postal_codes' => ['nullable', 'string'],
            'coverage_rules' => ['nullable', 'string'],
        ]);

        $area->update([
            'name' => $data['name'],
            'province' => trim($data['province']),
            'cities' => collect(explode(',', $data['cities'] ?? ''))->map(fn ($v) => trim($v))->filter()->values(),
            'postal_codes' => collect(explode(',', $data['postal_codes'] ?? ''))->map(fn ($v) => trim($v))->filter()->values(),
            'coverage_rules' => $data['coverage_rules'] ?? null,
        ]);

        return back()->with('success', "Delivery area '{$area->name}' updated successfully.");
    }

    public function toggle(DestinationArea $area)
    {
        $area->update(['is_active' => ! $area->is_active]);
        $status = $area->is_active ? 'activated' : 'paused';
        return back()->with('success', "Destination area '{$area->name}' {$status}.");
    }
}
