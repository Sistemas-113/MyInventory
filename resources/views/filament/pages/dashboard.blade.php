<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Widgets --}}
        <div>
            @foreach ($this->getHeaderWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>

        {{-- Footer Widgets --}}
        <div>
            @foreach ($this->getFooterWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
