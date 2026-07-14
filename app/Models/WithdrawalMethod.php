<?php

namespace App\Models;

use App\Enums\WithdrawalMethodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalMethod extends Model
{
    use HasFactory;

    protected $fillable = ['vendor_id', 'type', 'bank_name', 'account_name', 'account_number', 'is_default'];

    protected function casts(): array
    {
        return [
            'type' => WithdrawalMethodType::class,
            'account_name' => 'encrypted',
            'account_number' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
