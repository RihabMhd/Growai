<?php

namespace App\Http\Controllers\Api\Shops;

use App\Domain\Shopify\Models\Shop;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages the active shop context for the authenticated employee.
 *
 * The selected shop_id is stored in the user's session so the frontend
 * can omit it from every request — it is resolved once here and carried
 * forward by the session cookie.
 *
 * Route-model-bound shop routes (/api/shops/{shop}/...) do NOT use this
 * session value; they bind the shop directly from the URL segment.
 * This controller is only for the "shop switcher" UI surface.
 */
final class ShopSessionController extends Controller
{
    /**
     * GET /api/session/shop
     *
     * Return the currently active shop, or null if none is selected.
     */
    public function show(Request $request): JsonResponse
    {
        $shopId = $request->session()->get('active_shop_id');

        if ($shopId === null) {
            return response()->json(['shop' => null]);
        }

        $shop = Shop::find($shopId);

        // Guard against stale session holding a deleted shop ID
        if ($shop === null) {
            $request->session()->forget('active_shop_id');

            return response()->json(['shop' => null]);
        }

        return response()->json(['shop' => $shop]);
    }

    /**
     * PUT /api/session/shop
     *
     * Select a shop as the active context for the current session.
     * The authenticated employee must have access to the requested shop.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
        ]);

        $shop = Shop::findOrFail($validated['shop_id']);

        // Authorization: verify the authenticated user is allowed to access
        // this shop before storing it in session.
        $request->user()->cannot('view', $shop)
            ? abort(403, 'You do not have access to this shop.')
            : null;

        $request->session()->put('active_shop_id', $shop->id);

        return response()->json(['shop' => $shop]);
    }

    /**
     * DELETE /api/session/shop
     *
     * Clear the active shop from the session.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->session()->forget('active_shop_id');

        return response()->json(['shop' => null]);
    }
}