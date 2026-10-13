<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShopFollowController extends Controller
{
    public function index(): View
    {
        $shops = Auth::user()
            ->followedShops()
            ->with(['approvedSellerApplication', 'sellerApplication'])
            ->orderByDesc('shop_follows.created_at')
            ->get();

        return view('buyer.following.index', ['shops' => $shops]);
    }

    public function toggle(User $seller): RedirectResponse
    {
        abort_unless($seller->role === 'seller' || $seller->approvedSellerApplication, 404);
        abort_if((int) Auth::id() === (int) $seller->id, 403, 'You cannot follow your own shop.');

        $user = Auth::user();
        $result = $user->followedShops()->toggle($seller->id);
        $following = ! empty($result['attached']);

        $seller->loadMissing(['approvedSellerApplication', 'sellerApplication']);
        $shopName = $seller->shop_name
            ?: ($seller->approvedSellerApplication?->business_name
                ?: ($seller->sellerApplication?->business_name ?: $seller->name));

        if ($following) {
            return back()->with('followed_shop', $shopName);
        }

        return back()->with('success', 'You unfollowed this shop.');
    }
}
