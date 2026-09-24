<?php

namespace App\Actions\Wallet\Concerns;

use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Models\AgentSettlement;
use App\Models\AgentWallet;
use App\Models\AgentWalletTransaction;
use App\Models\User;
use App\Models\VendorOrder;

/**
 * Mirrors RecordsWalletTransaction for the agent ledger.
 */
trait RecordsAgentWalletTransaction
{
    private function recordAgentTransaction(
        AgentWallet $wallet,
        WalletTransactionType $type,
        WalletBalanceBucket $bucket,
        int $delta,
        ?VendorOrder $vendorOrder = null,
        ?AgentSettlement $settlement = null,
        ?string $reason = null,
        ?User $actor = null,
    ): void {
        AgentWalletTransaction::create([
            'agent_wallet_id' => $wallet->id,
            'vendor_order_id' => $vendorOrder?->id,
            'agent_settlement_id' => $settlement?->id,
            'type' => $type,
            'balance_bucket' => $bucket,
            'amount' => $delta,
            'reason' => $reason,
            'created_by' => $actor?->id,
        ]);

        $column = $bucket->column();
        $wallet->update([$column => $wallet->{$column} + $delta]);
    }
}
