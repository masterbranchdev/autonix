<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">

        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
            alignment="right"
        />

    </x-filament-panels::form>
</x-filament-panels::page>
