<?php

namespace App\Models;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Models\Scopes\BelongsToVendorScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorDocument extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToVendorScope);
    }

    protected $fillable = [
        'vendor_id', 'vendor_application_id', 'type', 'file_path', 'status',
        'rejection_reason', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => VendorDocumentType::class,
            'status' => VendorDocumentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorApplication(): BelongsTo
    {
        return $this->belongsTo(VendorApplication::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
