@extends('layouts.storefront')

@section('title', 'Your cart')

@section('content')
    <h1 class="text-lg font-semibold text-gray-900 mb-6">Your cart</h1>

    @if ($cart->activeItems->isEmpty() && $cart->savedItems->isEmpty())
        <div class="bg-white shadow rounded-lg p-6 text-sm text-gray-500">
            Your cart is empty. Browse the <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:underline">shop</a> to add products.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                @if ($cart->activeItems->isEmpty())
                    <div class="bg-white shadow rounded-lg p-6 text-sm text-gray-500">
                        Your cart is empty. Everything is saved for later below.
                    </div>
                @endif

                @foreach ($vendorGroups as $vendorId => $items)
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-700">
                            {{ $items->first()->product->vendor?->store?->name ?? 'Sold by marketplace' }}
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach ($items as $item)
                                <div class="p-4 flex gap-4">
                                    @php $thumb = $item->product->getFirstMediaUrl('images', 'thumb') ?: $item->product->getFirstMediaUrl('images'); @endphp
                                    <div class="h-20 w-20 shrink-0 rounded-md bg-gray-100 overflow-hidden flex items-center justify-center">
                                        @if ($thumb)
                                            <img src="{{ $thumb }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="text-gray-300 text-xs">No image</span>
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        <a href="{{ route('products.show', $item->product) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $item->product->name }}
                                        </a>
                                        @if ($item->variation)
                                            <p class="text-xs text-gray-500">{{ $item->variation->attributeValues->pluck('value')->implode(' / ') }}</p>
                                        @endif

                                        @if (! $item->isInStock())
                                            <p class="mt-1 text-xs text-red-600">No longer in stock — remove or save for later.</p>
                                        @elseif ($item->hasPriceDrifted())
                                            <p class="mt-1 text-xs text-amber-600">
                                                Price changed to ${{ number_format($item->currentUnitPrice() / 100, 2) }} since you added this.
                                            </p>
                                        @endif

                                        <div class="mt-2 flex items-center gap-4">
                                            <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <label for="quantity-{{ $item->id }}" class="sr-only">Quantity</label>
                                                <input id="quantity-{{ $item->id }}" type="number" name="quantity" value="{{ $item->quantity }}" min="0"
                                                       class="w-16 rounded-md border-gray-300 text-sm shadow-sm">
                                                <button type="submit" class="text-xs text-indigo-600 hover:underline">Update</button>
                                            </form>

                                            <form method="POST" action="{{ route('cart.items.save-for-later', $item) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-xs text-gray-500 hover:underline">Save for later</button>
                                            </form>

                                            <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="text-sm font-semibold text-gray-900">
                                        ${{ number_format($item->lineTotal() / 100, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if ($cart->savedItems->isNotEmpty())
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 text-sm font-medium text-gray-700">
                            Saved for later
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($cart->savedItems as $item)
                                <div class="p-4 flex items-center justify-between">
                                    <div>
                                        <a href="{{ route('products.show', $item->product) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $item->product->name }}
                                        </a>
                                        @if ($item->variation)
                                            <p class="text-xs text-gray-500">{{ $item->variation->attributeValues->pluck('value')->implode(' / ') }}</p>
                                        @endif
                                        <p class="text-xs text-gray-500">Qty {{ $item->quantity }} &middot; ${{ number_format($item->unit_price / 100, 2) }}</p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <form method="POST" action="{{ route('cart.items.move-to-cart', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs text-indigo-600 hover:underline">Move to cart</button>
                                        </form>
                                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-6 h-fit space-y-4">
                <h2 class="font-medium text-gray-900">Order summary</h2>

                @if ($cart->coupon)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Coupon <span class="font-medium text-gray-900">{{ $cart->coupon->code }}</span></span>
                        <form method="POST" action="{{ route('cart.coupon.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('cart.coupon.store') }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="code" placeholder="Coupon code" class="flex-1 rounded-md border-gray-300 text-sm shadow-sm">
                        <button type="submit" class="rounded-md bg-gray-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-900">Apply</button>
                    </form>
                @endif

                <dl class="space-y-2 text-sm border-t border-gray-100 pt-4">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Subtotal</dt>
                        <dd class="text-gray-900">${{ number_format($cart->subtotal() / 100, 2) }}</dd>
                    </div>
                    @if ($cart->discount() > 0)
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Discount</dt>
                            <dd class="text-green-700">-${{ number_format($cart->discount() / 100, 2) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-100 pt-2">
                        <dt>Estimated total</dt>
                        <dd>${{ number_format($cart->total() / 100, 2) }}</dd>
                    </div>
                </dl>

                <p class="text-xs text-gray-500">Tax and shipping are calculated at checkout.</p>

                @if ($cart->activeItems->isNotEmpty())
                    <a href="{{ route('checkout.index') }}" class="block text-center rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                        Proceed to checkout
                    </a>
                @endif
            </div>
        </div>
    @endif
@endsection
