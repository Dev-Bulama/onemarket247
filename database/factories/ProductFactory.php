<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'vendor_id' => Vendor::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-########')),
            'type' => ProductType::Simple,
            'status' => ProductStatus::Published,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->numberBetween(500, 50000),
            'manage_stock' => true,
            'stock_quantity' => fake()->numberBetween(0, 200),
            'stock_status' => StockStatus::InStock,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft, 'published_at' => null]);
    }

    public function pendingApproval(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::PendingApproval, 'published_at' => null]);
    }

    public function variable(): static
    {
        return $this->state(fn () => [
            'type' => ProductType::Variable,
            'price' => null,
            'stock_quantity' => null,
            'manage_stock' => false,
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn () => ['type' => ProductType::Digital, 'manage_stock' => false, 'stock_quantity' => null]);
    }
}
