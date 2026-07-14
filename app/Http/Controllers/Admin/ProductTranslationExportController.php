<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductTranslationExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        abort_unless(Auth::guard('admin')->user()?->can('products.update'), 403);

        $languages = Language::query()->where('is_active', true)->orderBy('code')->get();

        return response()->streamDownload(function () use ($languages) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['product_id', 'sku', 'name', 'missing_languages']);

            Product::query()
                ->with(['translations.language'])
                ->orderBy('name')
                ->chunk(200, function ($products) use ($handle, $languages) {
                    foreach ($products as $product) {
                        $translatedCodes = $product->translations->pluck('language.code')->filter()->all();
                        $missing = $languages->pluck('code')->diff($translatedCodes)->values();

                        if ($missing->isEmpty()) {
                            continue;
                        }

                        fputcsv($handle, [
                            $product->id,
                            $product->sku,
                            $product->name,
                            $missing->implode(','),
                        ]);
                    }
                });

            fclose($handle);
        }, 'missing-product-translations.csv', ['Content-Type' => 'text/csv']);
    }
}
