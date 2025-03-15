<x-filament-panels::page>
    {{-- Widgets de estadísticas --}}
    <div class="mb-8">
        @foreach ($this->getHeaderWidgets() as $widget)
            {{ $widget }}
        @endforeach
    </div>

    {{-- Tabla de pagos --}}
    <div class="border rounded-xl shadow-sm bg-white">
        {{ $this->contentTable }}
    </div>
</x-filament-panels::page>
