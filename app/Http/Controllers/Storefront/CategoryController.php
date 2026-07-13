<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends Controller
{
    use FiltersProducts;

    public function index(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('storefront.categories.index', ['categories' => $categories]);
    }

    public function show(Request $request, Category $category, ?Category $subcategory = null): View
    {
        if ($subcategory && $subcategory->parent_id !== $category->id) {
            throw new NotFoundHttpException;
        }

        $activeCategory = $subcategory ?? $category;
        $descendantIds = $activeCategory->descendantIds();

        $products = $this->filteredProducts(
            Product::query()->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $descendantIds)),
            $request,
        );

        $subcategories = $category->children()->where('is_active', true)->orderBy('sort_order')->get();

        return view('storefront.categories.show', [
            'category' => $category,
            'subcategory' => $subcategory,
            'subcategories' => $subcategories,
            'products' => $products,
            ...$this->filterOptions(),
        ]);
    }
}
