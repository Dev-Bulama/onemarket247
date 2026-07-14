<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Enums\VendorOrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Populates the catalog with realistic-looking demo content (25 categories,
 * 25 brands, 25 products spread across 5 vendors) so the storefront has
 * something to actually show — real rows, real images, a few real reviews
 * and real "sold" order items so Best Sellers isn't empty. Images are
 * generated locally with GD (a labelled solid-colour square) rather than
 * fetched from the network, so this seeder works offline and is fast.
 * Safe to re-run: skips entirely if 25+ demo products already exist.
 */
class DemoCatalogSeeder extends Seeder
{
    private const CATEGORIES = [
        'Electronics', 'Mobile Phones', 'Computers & Laptops', 'Home Appliances',
        'Furniture', 'Kitchenware', 'Men\'s Fashion', 'Women\'s Fashion',
        'Kids & Baby', 'Shoes', 'Bags & Luggage', 'Watches & Jewelry',
        'Beauty & Personal Care', 'Health & Wellness', 'Sports & Outdoors',
        'Toys & Games', 'Books & Stationery', 'Automotive', 'Tools & Hardware',
        'Pet Supplies', 'Groceries', 'Office Supplies', 'Musical Instruments',
        'Garden & Outdoor', 'Video Games',
    ];

    private const BRANDS = [
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

        $categories = collect(self::CATEGORIES)->values()->map(function (string $name, int $i) {
            $category = Category::factory()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'sort_order' => $i,
            ]);

            $this->attachPlaceholderImage($category, 'image', $name, self::PALETTE[$i % count(self::PALETTE)]);

            return $category;
        });

        $brands = collect(self::BRANDS)->values()->map(function (string $name, int $i) {
            $brand = Brand::factory()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'is_active' => true,
                'sort_order' => $i,
            ]);

            $this->attachPlaceholderImage($brand, 'logo', $name, self::PALETTE[($i + 4) % count(self::PALETTE)]);

            return $brand;
        });

        $vendors = collect(range(1, 5))->map(function (int $i) {
            $vendor = Vendor::factory()->create();
            Store::factory()->create(['vendor_id' => $vendor->id, 'is_featured' => $i <= 2]);

            return $vendor;
        });

        $reviewer = User::factory()->create([
            'name' => 'Demo Shopper',
            'email' => 'demo-reviewer@onemarket247.test',
            'user_type' => UserType::Customer,
        ]);

        collect(self::PRODUCTS)->values()->each(function (string $name, int $i) use ($categories, $brands, $vendors, $reviewer) {
            $vendor = $vendors[$i % $vendors->count()];
            $price = random_int(1500, 45000);
            $hasDiscount = $i % 3 === 0;

            $product = Product::factory()->create([
                'vendor_id' => $vendor->id,
                'brand_id' => $brands[$i % $brands->count()]->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.($i + 1),
                'sku' => 'DEMO-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'price' => $price,
                'compare_at_price' => $hasDiscount ? (int) round($price * 1.3) : null,
                'is_featured' => $i % 3 === 0,
                'view_count' => random_int(5, 2000),
            ]);

            $product->categories()->attach($categories[$i % $categories->count()]->id, ['is_primary' => true]);

            $this->attachPlaceholderImage($product, 'images', $name, self::PALETTE[$i % count(self::PALETTE)]);

            if ($i % 2 === 0) {
                ProductReview::factory()->approved()->create([
                    'product_id' => $product->id,
                    'customer_id' => $reviewer->id,
                    'rating' => random_int(3, 5),
                ]);
            }

            if ($i % 4 === 0) {
                $vendorOrder = VendorOrder::factory()->create([
                    'vendor_id' => $vendor->id,
                    'status' => VendorOrderStatus::Completed,
                ]);

                OrderItem::factory()->create([
                    'vendor_order_id' => $vendorOrder->id,
                    'product_id' => $product->id,
                    'quantity' => random_int(1, 8),
                ]);
            }
        });

        $this->command?->info('Seeded 25 categories, 25 brands, 5 vendors/stores, and 25 products.');
    }

    private function attachPlaceholderImage(Model $model, string $collection, string $label, string $hexColor): void
    {
        $path = $this->generatePlaceholderImage($label, $hexColor);

        $model->addMedia($path)->toMediaCollection($collection);
    }

    private function generatePlaceholderImage(string $text, string $hexColor, int $size = 640): string
    {
        [$r, $g, $b] = sscanf($hexColor, '#%02x%02x%02x');

        $image = imagecreatetruecolor($size, $size);
        $background = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $background);

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
