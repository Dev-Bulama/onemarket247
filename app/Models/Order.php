<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id', 'order_number', 'customer_id',
        'guest_name', 'guest_email', 'guest_phone',
        'shipping_full_name', 'shipping_phone', 'shipping_address_line_1', 'shipping_address_line_2',
        'shipping_country_id', 'shipping_state_id', 'shipping_city_id', 'shipping_postal_code',
        'currency_id', 'exchange_rate_snapshot', 'subtotal', 'discount_amount', 'shipping_amount', 'tax_amount', 'total',
        'coupon_code', 'status', 'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate_snapshot' => 'decimal:10',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'shipping_amount' => 'integer',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            $order->public_id ??= (string) Str::uuid();
        });

        static::created(function (self $order) {
            if ($order->order_number === null) {
                $order->updateQuietly(['order_number' => sprintf('OM-%d-%06d', $order->created_at->year, $order->id)]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(OrderStatusHistory::class, 'historyable');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function shippingCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'shipping_country_id');
    }

    public function shippingState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'shipping_state_id');
    }

    public function shippingCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'shipping_city_id');
    }

    public function customerName(): string
    {
        return $this->customer?->name ?? $this->guest_name ?? '';
    }

    public function customerEmail(): string
    {
        return $this->customer?->email ?? $this->guest_email ?? '';
    }

    public function isGuestOrder(): bool
    {
        return $this->customer_id === null;
    }

    /**
     * A registered customer notifies through their own account; a guest
     * order has no account to route through, so it notifies by email
     * address directly via Laravel's on-demand (anonymous) notifiable.
     */
    public function notifiable(): mixed
    {
        return $this->customer ?? Notification::route('mail', $this->guest_email);
    }
}
