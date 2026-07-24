<x-filament-panels::page>
    @if ($url = $this->currentImageUrl())
        <div class="mb-6">
            <p class="text-sm font-medium text-gray-700 mb-2">Current hero photo</p>
            <img src="{{ $url }}" alt="Current hero photo" class="w-full max-w-xl rounded-lg border border-gray-200 object-cover aspect-video">
            <button
                type="button"
                wire:click="removeImage"
                wire:confirm="Remove the current hero photo? The homepage will fall back to its default background until a new one is uploaded."
                class="mt-2 text-sm text-red-600 hover:underline"
            >
                Remove current photo
            </button>
        </div>
    @else
        <p class="mb-6 text-sm text-gray-500">No hero photo uploaded yet — the homepage is showing its default background.</p>
    @endif

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
