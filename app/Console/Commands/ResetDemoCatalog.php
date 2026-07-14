<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes everything DemoCatalogSeeder created (matched by its known
 * category/brand names, the DEMO- SKU prefix, and the demo vendor/reviewer
 * email domain) so the seeder can be re-run from scratch. Needed because
 * DemoCatalogSeeder skips entirely once 25+ demo products already exist,
 * so re-running it after upgrading the seeder (e.g. to add real stock
 * photos/category icons) does nothing to already-seeded data.
 */
class ResetDemoCatalog extends Command
{
    protected $signature = 'demo-catalog:reset {--force : Skip the confirmation prompt}';

    protected $description = 'Delete the demo catalog seeded by DemoCatalogSeeder so it can be reseeded';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This permanently deletes the demo catalog (products, categories, brands, demo vendors/stores, and their orders/reviews). Continue?')) {
            return self::FAILURE;
        }

        DB::transaction(function () {
            $products = Product::withTrashed()->where('sku', 'like', 'DEMO-%')->get();

            foreach ($products as $product) {
                foreach (OrderItem::where('product_id', $product->id)->get() as $item) {
                    $vendorOrder = $item->vendorOrder;
                    $item->delete();

                    if ($vendorOrder) {
                        $order = $vendorOrder->order;
                        $vendorOrder->delete();
                        $order?->delete();
                    }
                }

                ProductReview::withTrashed()->where('product_id', $product->id)->forceDelete();
                $product->forceDelete();
            }

            Category::withTrashed()->whereIn('name', array_keys(DemoCatalogSeeder::CATEGORIES))->get()
                ->each(fn (Category $category) => $category->forceDelete());

            Brand::withTrashed()->whereIn('name', DemoCatalogSeeder::BRANDS)->get()
                ->each(fn (Brand $brand) => $brand->forceDelete());

            $demoUsers = User::where('email', 'like', '%@onemarket247-demo.test')->get();

            foreach ($demoUsers as $user) {
                if ($vendor = $user->vendor) {
                    $vendor->store()->withoutGlobalScopes()->first()?->forceDelete();
                    $vendor->forceDelete();
                }

                $user->forceDelete();
            }
        });

        $this->info('Demo catalog cleared. Run `php artisan db:seed --class=DemoCatalogSeeder --force` to reseed with icons and real photos.');

        return self::SUCCESS;
    }
}
