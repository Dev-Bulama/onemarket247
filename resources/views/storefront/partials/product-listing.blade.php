@php
    $sort = request('sort', 'newest');
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-8">
    <aside class="md:col-span-1">
        <form method="GET" class="space-y-6 bg-white border border-gray-200 rounded-lg p-4">
            @foreach (request()->except(['category_id', 'brand_id', 'min_price', 'max_price', 'in_stock', 'sort', 'page']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div>
                <label for="filter-category" class="block text-sm font-medium text-gray-700">Category</label>
                <select id="filter-category" name="category_id" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 border px-2 py-1.5 text-sm">
                    <option value="">All categories</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}" @selected(request('category_id') == $categoryOption->id)>{{ $categoryOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-brand" class="block text-sm font-medium text-gray-700">Brand</label>
                <select id="filter-brand" name="brand_id" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 border px-2 py-1.5 text-sm">
                    <option value="">All brands</option>
                    @foreach ($brands as $brandOption)
                        <option value="{{ $brandOption->id }}" @selected(request('brand_id') == $brandOption->id)>{{ $brandOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <span class="block text-sm font-medium text-gray-700">Price</span>
                <div class="mt-1 flex items-center gap-2">
                    <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" step="0.01" placeholder="Min" class="w-full rounded-md border-gray-300 border px-2 py-1.5 text-sm">
                    <span class="text-gray-400">–</span>
                    <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" step="0.01" placeholder="Max" class="w-full rounded-md border-gray-300 border px-2 py-1.5 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="filter-in-stock" name="in_stock" value="1" @checked(request('in_stock')) class="rounded border-gray-300">
                <label for="filter-in-stock" class="text-sm text-gray-700">In stock only</label>
            </div>

            <div>
                <label for="filter-sort" class="block text-sm font-medium text-gray-700">Sort by</label>
                <select id="filter-sort" name="sort" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 border px-2 py-1.5 text-sm">
                    <option value="newest" @selected($sort === 'newest')>Newest</option>
                    <option value="price_asc" @selected($sort === 'price_asc')>Price: Low to High</option>
                    <option value="price_desc" @selected($sort === 'price_desc')>Price: High to Low</option>
                    <option value="name" @selected($sort === 'name')>Name</option>
                </select>
            </div>

            <button type="submit" class="w-full inline-flex justify-center rounded-md bg-brand-orange px-4 py-2 text-sm font-medium text-white hover:bg-brand-orange">
                Apply filters
            </button>
        </form>
    </aside>

    <div class="md:col-span-3">
        <p class="text-sm text-gray-500 mb-4">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}</p>

        @if ($products->isEmpty())
            <p class="text-sm text-gray-600">No products match your filters.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach ($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
