<x-filament-panels::page>
    <div class="grid gap-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($stats as $stat)
                {{ $stat }}
            @endforeach
        </div>

        <x-filament::section>
            <x-slot name="heading">
                Top 10 Productos más Vendidos
            </x-slot>

            {{ $topProductsTable }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
