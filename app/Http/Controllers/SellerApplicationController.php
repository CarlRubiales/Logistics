<?php

namespace App\Http\Controllers;

use App\Support\SellerApplicationFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $eligibilityRedirect = $this->guardEligibility($request);

        if ($eligibilityRedirect) {
            return $eligibilityRedirect;
        }

        return view('seller.apply', [
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $eligibilityRedirect = $this->guardEligibility($request);

        if ($eligibilityRedirect) {
            return $eligibilityRedirect;
        }

        $validated = $request->validate(SellerApplicationFields::applicationRules(), [
            'id_document.mimes' => 'Valid ID must be a JPG, PNG, or PDF file only.',
            'id_document.max' => 'Valid ID file size must not exceed 5MB.',
            'business_permit.mimes' => 'Business Permit must be a JPG, PNG, or PDF file only.',
            'business_permit.max' => 'Business Permit file size must not exceed 5MB.',
        ]);

        $request->user()->update([
            'contact_number' => $validated['contact_number'],
            'province' => $validated['province'],
            'municipality' => $validated['municipality'],
            'barangay' => $validated['barangay'],
            'street' => $validated['street'] ?? null,
            'house_number' => $validated['house_number'] ?? null,
        ]);

        SellerApplicationFields::createFromRequest($request, $request->user()->id);

        return redirect()
            ->route('seller.pending')
            ->with('success', 'Your seller application has been submitted for review.');
    }

    private function guardEligibility(Request $request): ?RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role === 'seller') {
            return redirect()->route('seller.dashboard');
        }

        if ($user->role !== 'buyer' || $user->provider !== 'google') {
            return redirect()
                ->route('shop.index')
                ->with('error', 'Seller applications from this menu are currently available for Google buyer accounts only.');
        }

        $application = $user->sellerApplication;

        if (! $application) {
            return null;
        }

        if ($application->status === 'pending') {
            return redirect()->route('seller.pending');
        }

        if ($application->status === 'approved') {
            return redirect()
                ->route('shop.index')
                ->with('error', 'Your seller application is approved, but seller access is not active yet.');
        }

        return redirect()
            ->route('shop.index')
            ->with('error', 'Your seller application status is '.str_replace('_', ' ', $application->status).'.');
    }
}
