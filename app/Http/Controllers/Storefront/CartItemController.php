<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Cart\UpdateCartItemQuantityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Support\Cart\CartResolver;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CartItemController extends Controller
{
    public function __construct(private readonly CartResolver $cartResolver) {}

    public function store(AddCartItemRequest $request, AddCartItemAction $action): RedirectResponse
    {
        $cart = $this->cartResolver->resolve();
        $product = Product::findOrFail($request->integer('product_id'));
        $variation = $request->filled('product_variation_id')
            ? ProductVariation::findOrFail($request->integer('product_variation_id'))
            : null;

        try {
            $action->handle($cart, $product, $variation, $request->integer('quantity'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', 'cart-item-added');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem, UpdateCartItemQuantityAction $action): RedirectResponse
    {
        $this->assertOwnsItem($cartItem);

        try {
            $action->handle($cartItem, $request->integer('quantity'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', 'cart-item-updated');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $this->assertOwnsItem($cartItem);

        $cartItem->delete();

        return back()->with('status', 'cart-item-removed');
    }

    public function saveForLater(CartItem $cartItem): RedirectResponse
    {
        $this->assertOwnsItem($cartItem);

        $cartItem->update(['saved_for_later' => true]);

        return back()->with('status', 'cart-item-saved');
    }

    public function moveToCart(CartItem $cartItem): RedirectResponse
    {
        $this->assertOwnsItem($cartItem);

        $cartItem->update(['saved_for_later' => false]);

        return back()->with('status', 'cart-item-restored');
    }

    private function assertOwnsItem(CartItem $cartItem): void
    {
        abort_unless($cartItem->cart_id === $this->cartResolver->resolve()->id, 403);
    }
}
