<aside class="hidden md:block w-56 flex-none">
    <div class="rounded-lg border border-gray-200 bg-white overflow-hidden sticky top-4">
        <div class="bg-indigo-600 px-4 py-3 text-white text-sm font-semibold flex items-center justify-between gap-2">
            <span>All Categories</span>
            <span class="text-xs bg-white/20 rounded px-1.5 py-0.5 whitespace-nowrap">{{ $totalProductCount }} products</span>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach ($navCategories as $category)
                @php $icon = $category->getFirstMediaUrl('image'); @endphp
                <li>
                    <a href="{{ route('categories.show', $category) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-50 text-indigo-500 text-xs overflow-hidden flex-none">
                            @if ($icon)
                                <img src="{{ $icon }}" class="h-full w-full object-cover" alt="">
                            @else
                                {{ Str::substr($category->name, 0, 1) }}
                            @endif
                        </span>
                        <span class="truncate">{{ $category->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
