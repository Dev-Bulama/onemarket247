<?php

namespace App\Console\Commands;

use App\Actions\Inventory\AdjustStockAction;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair for every vendor approved before ApproveVendorApplicationAction
 * started auto-creating a default warehouse: without one, a vendor's
 * products look "in stock" (Product::manage_stock/stock_quantity are just
 * display fields) but can never actually be bought — checkout's
 * CompleteCheckoutAction::selectWarehouseStock() requires a real
 * WarehouseStock row, which nothing else in the app ever creates. Safe to
 * re-run: only touches vendors with zero warehouses and products with zero
 * WarehouseStock rows.
 */
class BackfillVendorWarehouses extends Command
{
    protected $signature = 'vendors:backfill-warehouses';

    protected $description = "Create a default warehouse (and seed stock from each product's stock_quantity) for any vendor that has none";

    public function handle(AdjustStockAction $adjustStock): int
    {
        $vendors = Vendor::whereDoesntHave('warehouses')->get();

        if ($vendors->isEmpty()) {
            $this->info('Every vendor already has a warehouse — nothing to do.');

            return self::SUCCESS;
        }

        foreach ($vendors as $vendor) {
            DB::transaction(function () use ($vendor, $adjustStock) {
                $warehouse = Warehouse::create([
                    'vendor_id' => $vendor->id,
                    'name' => 'Main Warehouse',
                    'code' => 'MAIN',
                    'address' => $vendor->address,
                    'country_id' => $vendor->country_id,
                    'state_id' => $vendor->state_id,
                    'city_id' => $vendor->city_id,
                    'is_default' => true,
                    'is_active' => true,
                ]);

                $stocked = 0;

                foreach ($vendor->products()->where('manage_stock', true)->get() as $product) {
                    if ($product->stock_quantity <= 0 || $product->warehouseStocks()->exists()) {
                        continue;
                    }

                    $adjustStock->handle($warehouse, $product, $product->stock_quantity, 'Backfilled missing warehouse stock');
                    $stocked++;
                }

                $this->line("- {$vendor->business_name}: created warehouse, stocked {$stocked} product(s)");
            });
        }

        $this->info("Done — {$vendors->count()} vendor(s) fixed.");

        return self::SUCCESS;
    }
}
