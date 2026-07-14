<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\ReviewStatus;
use App\Enums\StockStatus;
use App\Models\Scopes\BelongsToVendorScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'vendor_id', 'brand_id', 'shipping_class_id', 'tax_class_id', 'name', 'slug', 'sku', 'type', 'status',
        'short_description', 'description', 'price', 'compare_at_price', 'cost_price',
        'manage_stock', 'stock_quantity', 'stock_status', 'low_stock_threshold',
        'weight', 'length', 'width', 'height', 'is_featured',
        'rejection_reason', 'reviewed_by', 'reviewed_at', 'published_at',
        'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'stock_status' => StockStatus::class,
            'manage_stock' => 'boolean',
            'is_featured' => 'boolean',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'view_count' => 'integer',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToVendorScope);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        $this->addMediaCollection('videos');
        $this->addMediaCollection('documents');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function shippingClass(): BelongsTo
    {
        return $this->belongsTo(ShippingClass::class);
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translationFor(?string $languageCode = null): ?ProductTranslation
    {
        $languageCode ??= app()->getLocale();

        return $this->translations()
            ->whereHas('language', fn ($query) => $query->where('code', $languageCode))
            ->first();
    }

    public function translatedName(?string $languageCode = null): string
    {
        return $this->translationFor($languageCode)?->name ?: $this->name;
    }

    public function translatedShortDescription(?string $languageCode = null): ?string
    {
        return $this->translationFor($languageCode)?->short_description ?: $this->short_description;
    }

    public function translatedDescription(?string $languageCode = null): ?string
    {
        return $this->translationFor($languageCode)?->description ?: $this->description;
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag_pivot');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function digitalFiles(): HasMany
    {
        return $this->hasMany(ProductDigitalFile::class);
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'related_products', 'product_id', 'related_product_id')
            ->withPivot('relation_type', 'sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', ReviewStatus::Approved);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function averageRating(): ?float
    {
        $average = $this->approvedReviews()->avg('rating');

        return $average !== null ? round((float) $average, 1) : null;
    }

    public function primaryCategory(): ?Category
    {
        return $this->categories()->wherePivot('is_primary', true)->first()
            ?? $this->categories()->first();
    }

    public function isInStock(): bool
    {
        if (! $this->manage_stock) {
            return true;
        }

        return $this->stock_status === StockStatus::InStock;
    }

    public function isVisibleToCustomers(): bool
    {
        return $this->status->isVisibleToCustomers();
    }

    /**
     * A single representative price for cards/listings: the product's own
     * price for simple/digital products, or the lowest active variation
     * price for a variable product (its "from" price).
     */
    public function displayPrice(): ?int
    {
        if ($this->type !== ProductType::Variable) {
            return $this->price;
        }

        return $this->variations()->where('is_active', true)->min('price');
    }

    /**
     * @return array{min: int, max: int}|null
     */
    public function displayPriceRange(): ?array
    {
        if ($this->type !== ProductType::Variable) {
            return null;
        }

        $prices = $this->variations()->where('is_active', true)->pluck('price');

        if ($prices->isEmpty()) {
            return null;
        }

        return ['min' => $prices->min(), 'max' => $prices->max()];
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->performOnCollections('images');
    }
}
