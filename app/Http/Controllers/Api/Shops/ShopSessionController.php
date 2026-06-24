<?php

namespace App\Http\Controllers\Api\Shops;

use App\Domain\Shopify\Models\Shop;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// manages the active shop context for the authenticated employee
final class ShopSessionController extends Controller
{

    public function show(Request $request): JsonResponse
    {
        $shopId = $request->session()->get('active_shop_id');

        if ($shopId === null) {
            return response()->json(['shop' => null]);
        }

        $shop = Shop::find($shopId);

        // guard against stale session holding a deleted shop id
        if ($shop === null) {
            $request->session()->forget('active_shop_id');

            return response()->json(['shop' => null]);
        }

        return response()->json(['shop' => $shop]);
    }


    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
        ]);

        $shop = Shop::findOrFail($validated['shop_id']);

        // verify the user is allowed to access this shop before storing it in session
        $request->user()->cannot('view', $shop)
            ? abort(403, 'You do not have access to this shop.')
            : null;

        $request->session()->put('active_shop_id', $shop->id);

        return response()->json(['shop' => $shop]);
    }


    public function destroy(Request $request): JsonResponse
    {
        $request->session()->forget('active_shop_id');

        return response()->json(['shop' => null]);
    }
}