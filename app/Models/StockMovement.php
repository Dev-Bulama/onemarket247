<?php

namespace App\Models;

use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Insert-only ledger — never updated or deleted (see the migration's
 * docblock). Every App\Actions\Inventory action writes exactly one row here
 * per bucket it touches.
 */
class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_stock_id', 'type', 'bucket', 'quantity_delta', 'reason',
        'reference_type', 'reference_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'bucket' => StockMovementBucket::class,
            'quantity_delta' => 'integer',
        ];
    }

    public function warehouseStock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
