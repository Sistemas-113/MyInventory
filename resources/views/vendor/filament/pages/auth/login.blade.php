<x-filament-panels::page.simple>
    <x-slot name="logo">
        <div class="flex justify-center">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-16">
        </div>
    </x-slot>

    {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.before') }}

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :full-width="true"
            :actions="$this->getCachedFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.after') }}
</x-filament-panels::page.simple>
