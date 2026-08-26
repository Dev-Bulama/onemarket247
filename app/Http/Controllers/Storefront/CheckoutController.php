<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Checkout\CompleteCheckoutAction;
use App\Actions\Checkout\InitiateCheckoutAction;
use App\Exceptions\CheckoutValidationException;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Models\CheckoutSession;
use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\State;
use App\Support\Cart\CartResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(CartResolver $cartResolver, InitiateCheckoutAction $action): View|RedirectResponse
    {
        $cart = $cartResolver->resolve();
        $cart->load(['activeItems.product.vendor.store', 'activeItems.variation', 'coupon']);

        if ($cart->activeItems->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'checkout-empty-cart');
        }

        $session = $action->handle($cart);

        $user = Auth::guard('web')->user();
        $defaultAddress = $user?->addresses()->where('is_default_shipping', true)->first();

        return view('storefront.checkout.index', [
            'cart' => $cart,
            'session' => $session,
            'defaultAddress' => $defaultAddress,
            'countries' => Country::where('is_active', true)->orderBy('name')->get(),
            'states' => State::where('is_active', true)->orderBy('name')->get(['id', 'country_id', 'name']),
            'cities' => City::where('is_active', true)->orderBy('name')->get(['id', 'state_id', 'name']),
            'paystackAvailable' => PaymentGateway::where('code', 'paystack')->where('is_active', true)->exists(),
            'bankTransferDetails' => $this->bankTransferDetails(),
        ]);
    }

    public function store(CheckoutRequest $request, CartResolver $cartResolver, CompleteCheckoutAction $action): RedirectResponse
    {
        $cart = $cartResolver->resolve();

        $session = CheckoutSession::where('idempotency_key', $request->string('checkout_session_key')->value())
            ->where('cart_id', $cart->id)
            ->first();

        if (! $session) {
            return redirect()->route('checkout.index')
                ->withErrors(['checkout' => 'Your checkout session could not be found. Please try again.']);
        }

        $user = Auth::guard('web')->user();

        $shippingData = [
            'customer_id' => $user?->id,
            'guest_name' => $user ? null : $request->string('full_name')->value(),
            'guest_email' => $user ? null : $request->string('email')->value(),
            'guest_phone' => $user ? null : ($request->string('phone')->value() ?: null),
            'shipping_full_name' => $request->string('full_name')->value(),
            'shipping_phone' => $request->string('phone')->value() ?: null,
            'shipping_address_line_1' => $request->string('address_line_1')->value(),
            'shipping_address_line_2' => $request->string('address_line_2')->value() ?: null,
            'shipping_country_id' => $request->integer('country_id'),
            'shipping_state_id' => $request->filled('state_id') ? $request->integer('state_id') : null,
            'shipping_city_id' => $request->filled('city_id') ? $request->integer('city_id') : null,
            'shipping_postal_code' => $request->string('postal_code')->value() ?: null,
            'payment_method' => $request->string('payment_method')->value() ?: null,
        ];

        try {
            $order = $action->handle($session, $shippingData);
        } catch (CheckoutValidationException|InsufficientStockException $e) {
            return redirect()->route('cart.index')->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('checkout.confirmation', $order);
    }

    public function confirmation(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['vendorOrders.orderItems', 'payments', 'shippingCountry', 'shippingState', 'shippingCity']);

        return view('storefront.checkout.confirmation', [
            'order' => $order,
            'payment' => $order->payments->sortByDesc('id')->first(),
            'bankTransferDetails' => $this->bankTransferDetails(),
        ]);
    }

    /**
     * @return array{bank_name: ?string, account_name: ?string, account_number: ?string}
     */
    private function bankTransferDetails(): array
    {
        return [
            'bank_name' => Setting::where('key', 'payment.bank_transfer.bank_name')->value('value'),
            'account_name' => Setting::where('key', 'payment.bank_transfer.account_name')->value('value'),
            'account_number' => Setting::where('key', 'payment.bank_transfer.account_number')->value('value'),
        ];
    }
}
