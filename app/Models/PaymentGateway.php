<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * public_key/secret_key/webhook_secret are encrypted casts (see
 * docs/architecture/10-security-architecture.md "Payment Security") —
 * never returned by any API/Filament read; Filament's TextInput fields for
 * these are configured write-only (see PaymentGatewayResource).
 */
class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_active', 'public_key', 'secret_key', 'webhook_secret', 'config'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'public_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'config' => 'array',
        ];
    }
}
