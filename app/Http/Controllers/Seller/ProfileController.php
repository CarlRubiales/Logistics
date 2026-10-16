<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('seller.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'last_name' => ['required', 'string', 'max:100', 'not_regex:/\d/'],
            'middle_initial' => ['nullable', 'string', 'max:4', 'not_regex:/\d/'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'contact_number' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:100'],
            'shop_name' => ['nullable', 'string', 'max:255'],
            'shop_description' => ['nullable', 'string', 'max:2000'],
        ], [
            'first_name.not_regex' => "You can't input a number in your name.",
            'last_name.not_regex' => "You can't input a number in your name.",
            'middle_initial.not_regex' => "You can't input a number in your name.",
        ]);

        $emailChanged = strtolower($validated['email']) !== strtolower($user->email);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->middle_initial = $validated['middle_initial'] ?? null;
        $user->sex = $validated['sex'];
        $user->contact_number = $validated['contact_number'];
        $user->date_of_birth = $validated['date_of_birth'];
        $user->age = Carbon::parse($validated['date_of_birth'])->age;
        $user->province = $validated['province'];
        $user->municipality = $validated['municipality'];
        $user->barangay = $validated['barangay'];
        $user->street = $validated['street'] ?? null;
        $user->house_number = $validated['house_number'] ?? null;
        $user->shop_name = $validated['shop_name'] ?? null;
        $user->shop_description = $validated['shop_description'] ?? null;
        $user->name = trim($validated['first_name'] . ' ' . $validated['last_name']);

        if ($emailChanged) {
            $user->email = strtolower($validated['email']);
            $user->email_verified = 0;
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with(
            'success',
            $emailChanged
                ? 'Profile updated. Your new email is unverified.'
                : 'Profile updated.'
        );
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password updated.');
    }
}
