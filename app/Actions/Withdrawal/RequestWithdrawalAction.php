<?php

namespace App\Actions\Withdrawal;

use App\Actions\Wallet\Concerns\LocksVendorWallet;
use App\Actions\Wallet\Concerns\RecordsWalletTransaction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Illuminate\Support\Facades\DB;

/**
 * The requested amount is moved to reserved_balance immediately, inside
 * the same locked transaction that checks available_balance — this,
 * combined with SELECT ... FOR UPDATE on the wallet row, is what makes
 * over-withdrawal impossible even if a vendor submits the request twice
 * in quick succession (see docs/architecture/09-lifecycles.md
 * "Withdrawal Lifecycle").
 */
class RequestWithdrawalAction
{
    use LocksVendorWallet, RecordsWalletTransaction;

    public function handle(Vendor $vendor, WithdrawalMethod $method, int $amount): Withdrawal
    {
        if ($method->vendor_id !== $vendor->id) {
            throw new InsufficientWalletBalanceException('This withdrawal method does not belong to this vendor.');
        }

        $minimum = (int) (Setting::where('key', 'finance.minimum_withdrawal')->first()?->typed_value ?? 0);

        if ($amount < $minimum) {
            throw new InsufficientWalletBalanceException("The minimum withdrawal amount is {$minimum}.");
        }

        return DB::transaction(function () use ($vendor, $method, $amount) {
            $wallet = $this->lockedWallet($vendor);

            if ($wallet->available_balance < $amount) {
                throw new InsufficientWalletBalanceException('Not enough available balance to request this withdrawal.');
            }

            $withdrawal = Withdrawal::create([
                'vendor_id' => $vendor->id,
                'withdrawal_method_id' => $method->id,
                'amount' => $amount,
                'status' => WithdrawalStatus::Pending,
            ]);

            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalHold, WalletBalanceBucket::Available, -$amount, withdrawal: $withdrawal);
            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalHold, WalletBalanceBucket::Reserved, $amount, withdrawal: $withdrawal);

            return $withdrawal->fresh();
        });
    }
}
