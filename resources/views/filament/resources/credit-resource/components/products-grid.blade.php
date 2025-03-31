@php
    $details = $getRecord()->details;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4">
    @foreach($details as $detail)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow duration-200">
            <div class="p-4 sm:p-6">
                <!-- Encabezado del Producto -->
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $detail->product_name }}
                        </h3>
                        <div class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-500/20 dark:text-primary-400">
                            {{ $detail->identifier_type }}: {{ $detail->identifier }}
                        </div>
                    </div>
                    @if($detail->provider)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            {{ $detail->provider->name }}
                        </span>
                    @endif
                </div>

                <!-- Detalles del Producto -->
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-m-cube" class="w-4 h-4 inline-block mr-1" />
                            Cantidad
                        </span>
                        <span class="mt-1 font-semibold text-gray-900 dark:text-white">
                            {{ $detail->quantity }} unidades
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-m-banknotes" class="w-4 h-4 inline-block mr-1" />
                            Precio Unitario
                        </span>
                        <span class="mt-1 font-semibold text-gray-900 dark:text-white">
                            $ {{ number_format($detail->unit_price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Descripción si existe -->
                @if($detail->product_description)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-m-information-circle" class="w-4 h-4 inline-block mr-1" />
                            {{ $detail->product_description }}
                        </p>
                    </div>
                @endif

                <!-- Subtotal -->
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
                        $ {{ number_format($detail->subtotal, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    @endforeach
</div>
