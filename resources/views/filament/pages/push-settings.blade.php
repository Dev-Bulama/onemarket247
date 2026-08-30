<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
        <p class="mt-2 text-sm text-gray-500">Save your changes first, then use "Send test push" above to confirm they actually work before relying on push for real messages.</p>
    </form>
</x-filament-panels::page>
