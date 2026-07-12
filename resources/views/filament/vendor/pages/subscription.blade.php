<x-filament-panels::page>
    @php
        $vendor = $this->getVendor();
        $current = $vendor->currentSubscription();
    @endphp

    <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-sm text-gray-500">Current plan</p>
        <p class="text-lg font-semibold">{{ $current?->plan?->name ?? 'No active plan' }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->getPlans() as $plan)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex flex-col">
                <p class="text-base font-semibold">{{ $plan->name }}</p>
                <p class="text-sm text-gray-500 mb-2">
                    {{ $plan->isFree() ? 'Free' : '$'.number_format($plan->price / 100, 2).' / '.$plan->billing_period->getLabel() }}
                </p>
                <p class="text-sm text-gray-600 mb-4">{{ $plan->description }}</p>

                @if ($current?->vendor_subscription_plan_id === $plan->id)
                    <span class="mt-auto inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                        Current plan
                    </span>
                @elseif ($plan->isFree())
                    <x-filament::button wire:click="switchTo({{ $plan->id }})" class="mt-auto">
                        Switch to this plan
                    </x-filament::button>
                @else
                    <p class="mt-auto text-xs text-gray-500">Contact support to upgrade.</p>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
