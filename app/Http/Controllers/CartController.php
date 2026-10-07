<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddCartItem;
use App\Actions\Cart\ClearCart;
use App\Actions\Cart\RemoveCartItem;
use App\Actions\Cart\UpdateCartItem;
use App\Enums\District;
use App\Exceptions\CartException;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Cart\CartCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, CartCalculator $calculator): View
    {
        abort_unless($request->user()?->can('shop'), 403);

        $user = $request->user();
        $dealer = $user->dealer()->firstOrFail();
        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('dealer_id', $dealer->id)
            ->with(['items.product.unit', 'items.product.brand'])
            ->first();

        return view('cart.index', [
            'dealer' => $dealer,
            'summary' => $calculator->summarize($cart, $dealer),
            'districts' => District::cases(),
        ]);
    }

    public function store(StoreCartItemRequest $request, AddCartItem $add): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        try {
            $add->execute($request->user(), $product, $request->integer('quantity'));
        } catch (CartException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return back()->with('status', __('Added to cart.'));
    }

    public function update(UpdateCartItemRequest $request, CartItem $item, UpdateCartItem $update): RedirectResponse
    {
        $this->owned($request, $item);

        try {
            $update->execute($item, $request->integer('quantity'));
        } catch (CartException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return back()->with('status', __('Cart updated.'));
    }

    public function destroy(Request $request, CartItem $item, RemoveCartItem $remove): RedirectResponse
    {
        abort_unless($request->user()?->can('shop'), 403);
        $this->owned($request, $item);
        $remove->execute($item);

        return back()->with('status', __('Removed from cart.'));
    }

    public function clear(Request $request, ClearCart $clear): RedirectResponse
    {
        abort_unless($request->user()?->can('shop'), 403);

        $cart = Cart::query()
            ->where('user_id', $request->user()->id)
            ->where('dealer_id', $request->user()->dealer_id)
            ->first();

        if ($cart !== null) {
            $clear->execute($cart);
        }

        return back()->with('status', __('Cart cleared.'));
    }

    private function owned(Request $request, CartItem $item): void
    {
        $item->loadMissing('cart');
        abort_unless(
            $item->cart->user_id === $request->user()->id
            && $item->cart->dealer_id === $request->user()->dealer_id,
            404,
        );
    }
}
