<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" color="primary">
                    Proses Top Up PPOB
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
