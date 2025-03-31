<x-filament::table>
    <x-slot name="header">
        <x-filament::table.header-cell>
            Cuota
        </x-filament::table.header-cell>
        <x-filament::table.header-cell>
            Monto
        </x-filament::table.header-cell>
        <x-filament::table.header-cell>
            Vencimiento
        </x-filament::table.header-cell>
        <x-filament::table.header-cell>
            Fecha de Pago
        </x-filament::table.header-cell>
        <x-filament::table.header-cell>
            Estado
        </x-filament::table.header-cell>
    </x-slot>

    @foreach($installments as $installment)
        <x-filament::table.row>
            @foreach($installment as $key => $value)
                <x-filament::table.cell>
                    @if($key === 'Estado')
                        <x-filament::badge
                            :color="match($value) {
                                'Pendiente' => 'warning',
                                'Pagada' => 'success',
                                'Vencida' => 'danger',
                                default => 'gray'
                            }"
                        >
                            {{ $value }}
                        </x-filament::badge>
                    @else
                        {{ $value }}
                    @endif
                </x-filament::table.cell>
            @endforeach
        </x-filament::table.row>
    @endforeach
</x-filament::table>
