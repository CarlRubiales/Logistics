<?php

namespace App\Http\Controllers;

use App\Models\DestinationArea;
use App\Models\RiderAreaAssignment;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RiderController extends Controller
{
    public function registerForm() { return view('riders.register'); }

    public function register(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'min:8', 'confirmed'], 'phone' => ['required', 'string', 'max:40'], 'vehicle_type' => ['required', 'string', 'max:80'], 'license_number' => ['nullable', 'string', 'max:80']]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'rider']);
        RiderProfile::create(['user_id' => $user->id, 'phone' => $data['phone'], 'vehicle_type' => $data['vehicle_type'], 'license_number' => $data['license_number'] ?? null, 'registration_status' => 'pending']);
        return redirect()->route('login')->with('status', 'Registration submitted. An administrator must approve your rider account before access is enabled.');
    }

    public function index()
    {
        $riders = RiderProfile::with(['user', 'areas'])->latest()->paginate(20);
        $areas = DestinationArea::where('is_active', true)->orderBy('name')->get();
        return view('riders.index', compact('riders', 'areas'));
    }

    public function approve(Request $request, RiderProfile $rider)
    {
        $rider->update(['registration_status' => $request->boolean('approved') ? 'approved' : 'disapproved', 'approved_at' => $request->boolean('approved') ? now() : null, 'approved_by' => $request->user()->id]);
        return back()->with('success', 'Rider registration status updated.');
    }

    public function assignArea(Request $request, RiderProfile $rider)
    {
        $data = $request->validate(['destination_area_id' => ['required', 'exists:destination_areas,id']]);
        RiderAreaAssignment::updateOrCreate(['rider_profile_id' => $rider->id, 'destination_area_id' => $data['destination_area_id']], ['is_active' => true, 'is_primary' => $rider->areaAssignments()->doesntExist()]);
        return back()->with('success', 'Delivery area assigned to rider.');
    }

    public function dashboard(Request $request)
    {
        $rider = $request->user()->riderProfile?->load('areas');
        abort_unless($rider && $rider->registration_status === 'approved', 403, 'Your rider account is awaiting administrator approval.');
        $parcels = $rider->parcels()->with('area')->latest('assigned_at')->paginate(15);
        return view('riders.dashboard', compact('rider', 'parcels'));
    }
}
