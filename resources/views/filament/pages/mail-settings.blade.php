<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit">
                Save
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                wire:click="sendTestEmail"
            >
                Send test email to me
            </x-filament::button>
        </div>
        <p class="mt-2 text-sm text-gray-500">Save your changes first, then send a test email to confirm they actually work before vendors and customers start relying on them.</p>
    </form>
</x-filament-panels::page>
