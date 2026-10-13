<?php

namespace App\Http\Controllers;

use App\Models\DestinationArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreasController extends Controller
{
    public function index()
    {
        $storeId = DB::table('stores')->value('id');
        $areas = DestinationArea::withCount(['parcels', 'riders'])->where('store_id', $storeId)->orderBy('name')->get();
        return view('areas.index', compact('areas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:30'], 'cities' => ['nullable', 'string'], 'postal_codes' => ['nullable', 'string'], 'coverage_rules' => ['nullable', 'string']]);
        $storeId = DB::table('stores')->value('id');
        abort_unless($storeId, 422, 'Create a store before creating delivery areas.');
        DestinationArea::create(['store_id' => $storeId, 'name' => $data['name'], 'code' => strtoupper($data['code']), 'cities' => collect(explode(',', $data['cities'] ?? ''))->map(fn ($v) => trim($v))->filter()->values(), 'postal_codes' => collect(explode(',', $data['postal_codes'] ?? ''))->map(fn ($v) => trim($v))->filter()->values(), 'coverage_rules' => $data['coverage_rules'] ?? null, 'is_active' => true]);
        return back()->with('success', 'Destination area created.');
    }

    public function toggle(DestinationArea $area)
    {
        $area->update(['is_active' => ! $area->is_active]);
        return back()->with('success', 'Destination area status updated.');
    }
}
