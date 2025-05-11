<x-filament::page>
    <x-filament::form wire:submit="merge">
        {{ $this->form }}
        <x-filament::button type="submit" class="mt-4">
            Spieler zusammenführen
        </x-filament::button>
    </x-filament::form>
</x-filament::page>
