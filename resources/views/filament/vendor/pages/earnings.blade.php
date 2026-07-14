<x-filament-panels::page>
    @php
        $wallet = $this->getWallet();
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-lg font-semibold">${{ number_format($wallet->pending_balance / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Available</p>
            <p class="text-lg font-semibold text-emerald-600">${{ number_format($wallet->available_balance / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Reserved (withdrawing)</p>
            <p class="text-lg font-semibold">${{ number_format($wallet->reserved_balance / 100, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500">Withdrawn</p>
            <p class="text-lg font-semibold">${{ number_format($wallet->withdrawn_balance / 100, 2) }}</p>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
