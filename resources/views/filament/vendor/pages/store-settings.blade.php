<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
    </form>

    <div
        class="mt-8 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        x-data="{ enabled: @js($this->isLocationSharingEnabled()) }"
        x-init="
            if (enabled && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => $wire.reportLocation(position.coords.latitude, position.coords.longitude, position.coords.accuracy),
                    () => {},
                )
            }
        "
    >
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Live location sharing</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            When enabled, OneMarket247 admins can see your current location while you're using this page. This only
            shares your location while a OneMarket247 page is open, never in the background.
        </p>

        <x-filament::button
            color="gray"
            class="mt-4"
            wire:click="toggleLocationSharing"
            x-on:click="enabled = !enabled"
        >
            <span x-show="enabled" x-cloak>Turn off location sharing</span>
            <span x-show="!enabled" x-cloak>Turn on location sharing</span>
        </x-filament::button>
    </div>
</x-filament-panels::page>
