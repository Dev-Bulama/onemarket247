<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
        <p class="mt-2 text-sm text-gray-500">Changes here take effect the next time each installed app starts — no app-store update required.</p>
    </form>
</x-filament-panels::page>
