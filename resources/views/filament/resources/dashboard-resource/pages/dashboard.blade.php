<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-8">
        @foreach ($this->getHeaderWidgets() as $widget)
            @livewire($widget)
        @endforeach
    </div>

    <x-filament::section class="mt-8">
        <div class="grid grid-cols-1 gap-4 lg:gap-8">
            @foreach ($this->getFooterWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
