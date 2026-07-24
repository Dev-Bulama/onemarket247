<aside class="hidden md:block w-56 flex-none">
    <div class="rounded-xl border border-line bg-white overflow-hidden shadow-sm sticky top-28">
        <div class="bg-brand-green px-4 py-3 text-white text-sm font-semibold flex items-center justify-between gap-2">
            <span>All Categories</span>
            <span class="text-xs bg-white/20 rounded px-1.5 py-0.5 whitespace-nowrap">{{ $totalProductCount }} products</span>
        </div>
        <ul class="divide-y divide-line">
            @foreach ($navCategories as $category)
                <li>
                    <a href="{{ route('categories.show', $category) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-body hover:bg-surface hover:text-brand-orange">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-50 text-brand-orange text-xs flex-none">
                            <i class="{{ $category->displayIcon() }}" aria-hidden="true"></i>
                        </span>
                        <span class="truncate">{{ $category->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
