<?php

namespace Database\Seeders;

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ReviewStatus;
use App\Enums\StockStatus;
use App\Enums\StoreStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\Warehouse;
use App\Support\StockImageDownloader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates the catalog with realistic-looking demo content (25 categories,
 * 25 brands, 25 products spread across 5 vendors) so the storefront has
 * something to actually show — real rows, real stock photos, a few real
 * reviews and real "sold" order items so Best Sellers isn't empty. Each
 * image is a deterministic real stock photo fetched over HTTP; if that
 * fails (e.g. no outbound internet), a locally generated placeholder is
 * used instead so this seeder never hard-fails offline.
 *
 * Deliberately builds every record with plain Model::create() instead of
 * factories: factories call fake() internally (fakerphp/faker is a
 * require-dev package), which is unavailable on a production
 * `composer install --no-dev` install where this seeder is meant to run.
 *
 * Safe to re-run: skips entirely if 25+ demo products already exist.
 */
class DemoCatalogSeeder extends Seeder
{
    public const CATEGORIES = [
        'Electronics' => 'fa-solid fa-tv',
        'Mobile Phones' => 'fa-solid fa-mobile-screen',
        'Computers & Laptops' => 'fa-solid fa-laptop',
        'Home Appliances' => 'fa-solid fa-blender',
        'Furniture' => 'fa-solid fa-couch',
        'Kitchenware' => 'fa-solid fa-kitchen-set',
        'Men\'s Fashion' => 'fa-solid fa-shirt',
        'Women\'s Fashion' => 'fa-solid fa-vest',
        'Kids & Baby' => 'fa-solid fa-baby',
        'Shoes' => 'fa-solid fa-shoe-prints',
        'Bags & Luggage' => 'fa-solid fa-suitcase',
        'Watches & Jewelry' => 'fa-solid fa-gem',
        'Beauty & Personal Care' => 'fa-solid fa-spa',
        'Health & Wellness' => 'fa-solid fa-heart-pulse',
        'Sports & Outdoors' => 'fa-solid fa-basketball',
        'Toys & Games' => 'fa-solid fa-puzzle-piece',
        'Books & Stationery' => 'fa-solid fa-book',
        'Automotive' => 'fa-solid fa-car',
        'Tools & Hardware' => 'fa-solid fa-toolbox',
        'Pet Supplies' => 'fa-solid fa-paw',
        'Groceries' => 'fa-solid fa-basket-shopping',
        'Office Supplies' => 'fa-solid fa-briefcase',
        'Musical Instruments' => 'fa-solid fa-music',
        'Garden & Outdoor' => 'fa-solid fa-seedling',
        'Video Games' => 'fa-solid fa-gamepad',
    ];

    public const BRANDS = [
        'Aurora', 'Bexley', 'Coral Peak', 'Driftwood', 'Everline', 'Falcon Ridge',
        'Glenmore', 'Harborlight', 'Ironclad', 'Juniper', 'Kestrel', 'Lumen',
        'Meridian', 'Northgate', 'Orbis', 'Pinewood', 'Quartz', 'Ravello',
        'Silverline', 'Tidewater', 'Umbra', 'Vantage', 'Westfield', 'Yonder',
        'Zenith',
    ];

    private const PRODUCTS = [
        'Wireless Bluetooth Earbuds', 'Noise-Cancelling Over-Ear Headphones', 'Smart Fitness Watch',
        'Portable Power Bank 20000mAh', '4K Ultra HD Action Camera', 'Mechanical Gaming Keyboard',
        'Wireless Ergonomic Mouse', 'Stainless Steel Water Bottle', 'Non-Stick Cookware Set',
        'Memory Foam Pillow', 'Cotton Bath Towel Set', 'Ceramic Coffee Mug Set',
        'Men\'s Slim Fit Denim Jeans', 'Women\'s Casual Summer Dress', 'Unisex Running Sneakers',
        'Leather Crossbody Bag', 'Classic Analog Wrist Watch', 'Kids\' Building Blocks Set',
        'Adjustable Yoga Mat', 'Stainless Steel Vacuum Flask', 'LED Desk Lamp',
        'Portable Bluetooth Speaker', 'Electric Hand Blender', 'Organic Face Moisturizer',
        'Wireless Phone Charging Pad',
    ];

    private const VENDOR_NAMES = [
        'Prime Trading Co.', 'Northstar Retail Ltd', 'Coastal Supplies Inc.',
        'Urban Market Group', 'Golden Gate Traders',
    ];

    private const PALETTE = [
        '#4f46e5', '#059669', '#d97706', '#dc2626', '#2563eb',
        '#7c3aed', '#db2777', '#0891b2', '#65a30d', '#ea580c',
    ];

    public function run(): void
    {
        if (Product::where('sku', 'like', 'DEMO-%')->count() >= 25) {
            $this->command?->info('Demo catalog already seeded — skipping.');

            return;
        }

        // Wrapped in a transaction so a failure partway through (e.g. a
        // network hiccup fetching a stock photo) rolls back cleanly instead
        // of leaving partial rows that collide on unique slugs/SKUs the next
        // time this seeder runs.
        DB::transaction(function () {
            $this->seed();
        });
    }

    private function seed(): void
    {
        $categories = collect(array_keys(self::CATEGORIES))->values()->map(function (string $name, int $i) {
            $icon = self::CATEGORIES[$name];
            $category = Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'icon' => $icon,
                'description' => "Explore our range of {$name} products.",
                'is_active' => true,
                'sort_order' => $i,
            ]);

            $this->attachPlaceholderImage($category, 'image', $name, self::PALETTE[$i % count(self::PALETTE)]);

            return $category;
        });

        $brands = collect(self::BRANDS)->values()->map(function (string $name, int $i) {
            $brand = Brand::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "{$name} — quality products you can trust.",
                'is_active' => true,
                'sort_order' => $i,
            ]);

            $this->attachPlaceholderImage($brand, 'logo', $name, self::PALETTE[($i + 4) % count(self::PALETTE)]);

            return $brand;
        });

        $vendors = collect(self::VENDOR_NAMES)->values()->map(function (string $name, int $i) {
            $owner = User::create([
                'name' => $name.' Owner',
                'email' => 'demo-vendor'.($i + 1).'@onemarket247-demo.test',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(24)),
                'user_type' => UserType::VendorOwner,
                'status' => UserStatus::Active,
            ]);

            $vendor = Vendor::create([
                'user_id' => $owner->id,
                'business_name' => $name,
                'registration_number' => 'REG-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'tax_identification_number' => 'TIN-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'status' => VendorStatus::Approved,
                'commission_rate' => 10,
                'is_verified' => true,
                'is_featured' => $i < 2,
                'bank_name' => 'Demo Trust Bank',
                'bank_account_name' => $name,
                'bank_account_number' => str_pad((string) ($i + 1), 10, '0', STR_PAD_LEFT),
                'approved_at' => now(),
            ]);

            Store::create([
                'vendor_id' => $vendor->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "{$name} is a trusted seller on ".config('app.name').'.',
                'email' => 'store'.($i + 1).'@onemarket247-demo.test',
                'phone' => '+1555000'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'status' => StoreStatus::Active,
                'is_verified' => true,
                'is_featured' => $i < 2,
            ]);

            // Every demo product below is marked manage_stock + in-stock, but
            // that's just a denormalized display flag — checkout actually
            // requires a real WarehouseStock row (see
            // CompleteCheckoutAction::selectWarehouseStock()). Without this,
            // every demo product would look purchasable but fail at checkout
            // with "No single warehouse has enough stock to fulfil this item."
            $warehouse = Warehouse::create([
                'vendor_id' => $vendor->id,
                'name' => $name.' Main Warehouse',
                'code' => 'WH-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'is_default' => true,
                'is_active' => true,
            ]);

            return [$vendor, $warehouse];
        });

        $reviewer = User::create([
            'name' => 'Demo Shopper',
            'email' => 'demo-reviewer@onemarket247-demo.test',
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(24)),
            'user_type' => UserType::Customer,
            'status' => UserStatus::Active,
        ]);

        $country = Country::first();
        $currency = Currency::where('is_default', true)->first() ?? Currency::first();

        collect(self::PRODUCTS)->values()->each(function (string $name, int $i) use ($categories, $brands, $vendors, $reviewer, $country, $currency) {
            [$vendor, $warehouse] = $vendors[$i % $vendors->count()];
            // Naira-scale minor units (kobo) — realistic ₦1,500-₦450,000 range,
            // not the old USD-cents-scale numbers left over from before NGN
            // became the default/settlement currency.
            $price = random_int(150_000, 45_000_000);
            $hasDiscount = $i % 3 === 0;

            // Every other product goes on flash sale so the homepage rail has
            // real, DB-driven data; the last one is deliberately expired to
            // prove expired campaigns are excluded (see Product::onFlashSale()).
            $isFlashSale = $i % 2 === 0;
            $isExpiredFlashSale = $i === (count(self::PRODUCTS) - 1);
            $flashSalePrice = $isFlashSale ? (int) round($price * 0.75) : $price;
            $stockQuantity = random_int(5, 200);

            $product = Product::create([
                'vendor_id' => $vendor->id,
                'brand_id' => $brands[$i % $brands->count()]->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.($i + 1),
                'sku' => 'DEMO-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'type' => ProductType::Simple,
                'status' => ProductStatus::Published,
                'short_description' => "A great {$name} at a great price.",
                'description' => "This {$name} is one of our most popular items, chosen by shoppers for its quality and value.",
                'price' => $isFlashSale ? $flashSalePrice : $price,
                'compare_at_price' => ($hasDiscount || $isFlashSale) ? (int) round($price * 1.3) : null,
                'flash_sale_starts_at' => $isFlashSale ? now()->subHours(3) : null,
                'flash_sale_ends_at' => match (true) {
                    $isExpiredFlashSale => now()->subHours(2),
                    $isFlashSale => now()->addHours(random_int(4, 30)),
                    default => null,
                },
                'manage_stock' => true,
                'stock_quantity' => $stockQuantity,
                'stock_status' => StockStatus::InStock,
                'published_at' => now(),
                'is_featured' => $i % 3 === 0,
                'view_count' => random_int(5, 2000),
            ]);

            $product->categories()->attach($categories[$i % $categories->count()]->id, ['is_primary' => true]);

            app(AdjustStockAction::class)->handle($warehouse, $product, $stockQuantity, 'Initial demo catalog stock');

            $this->attachPlaceholderImage($product, 'images', $name, self::PALETTE[$i % count(self::PALETTE)]);

            if ($i % 2 === 0) {
                ProductReview::create([
                    'product_id' => $product->id,
                    'customer_id' => $reviewer->id,
                    'rating' => random_int(3, 5),
                    'title' => 'Happy with this purchase',
                    'body' => "The {$name} met my expectations — good quality and arrived quickly.",
                    'status' => ReviewStatus::Approved,
                    'is_verified_purchase' => true,
                ]);
            }

            if ($i % 4 === 0 && $country && $currency) {
                $order = Order::create([
                    'customer_id' => null,
                    'guest_name' => 'Demo Buyer',
                    'guest_email' => 'demo-buyer@onemarket247-demo.test',
                    'guest_phone' => null,
                    'shipping_full_name' => 'Demo Buyer',
                    'shipping_phone' => null,
                    'shipping_address_line_1' => '123 Demo Street',
                    'shipping_country_id' => $country->id,
                    'currency_id' => $currency->id,
                    'subtotal' => $price,
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_amount' => 0,
                    'total' => $price,
                    'status' => OrderStatus::Completed,
                    'placed_at' => now(),
                ]);

                $vendorOrder = VendorOrder::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendor->id,
                    'subtotal' => $price,
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_amount' => 0,
                    'total' => $price,
                    'status' => VendorOrderStatus::Completed,
                ]);

                $quantity = random_int(1, 8);

                OrderItem::create([
                    'vendor_order_id' => $vendorOrder->id,
                    'product_id' => $product->id,
                    'product_variation_id' => null,
                    'product_name' => $name,
                    'sku' => $product->sku,
                    'unit_price' => $price,
                    'quantity' => $quantity,
                    'line_total' => $price * $quantity,
                ]);
            }
        });

        $this->command?->info('Seeded 25 categories, 25 brands, 5 vendors/stores, and 25 products.');
    }

    private function attachPlaceholderImage(Model $model, string $collection, string $label, string $hexColor): void
    {
        $seed = $collection.'-'.Str::slug($label);
        $path = StockImageDownloader::download($seed) ?? $this->generatePlaceholderImage($label, $hexColor);

        $model->addMedia($path)->toMediaCollection($collection);
    }

    private function generatePlaceholderImage(string $text, string $hexColor, int $size = 640): string
    {
        [$r, $g, $b] = sscanf($hexColor, '#%02x%02x%02x');

        $image = imagecreatetruecolor($size, $size);

        for ($row = 0; $row < $size; $row++) {
            $blend = $row / $size;
            $rowColor = imagecolorallocate(
                $image,
                (int) ($r + (255 - $r) * $blend * 0.35),
                (int) ($g + (255 - $g) * $blend * 0.35),
                (int) ($b + (255 - $b) * $blend * 0.35),
            );
            imageline($image, 0, $row, $size, $row, $rowColor);
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $font = 5;
        $lines = explode("\n", wordwrap($text, 18, "\n"));
        $lineHeight = imagefontheight($font) + 6;
        $totalHeight = count($lines) * $lineHeight;
        $y = (int) (($size - $totalHeight) / 2);

        foreach ($lines as $line) {
            $width = imagefontwidth($font) * strlen($line);
            $x = (int) (($size - $width) / 2);
            imagestring($image, $font, $x, $y, $line, $white);
            $y += $lineHeight;
        }

        $directory = storage_path('app/tmp-seed-images');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/'.Str::random(16).'.jpg';
        imagejpeg($image, $path, 85);
        imagedestroy($image);

        return $path;
    }
}
