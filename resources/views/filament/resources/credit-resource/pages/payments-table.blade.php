<x-filament::section>
    <x-slot name="heading">
        Historial de Pagos
    </x-slot>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left">N° Cuota</th>
                    <th class="px-4 py-2 text-left">Monto</th>
                    <th class="px-4 py-2 text-left">Fecha Vencimiento</th>
                    <th class="px-4 py-2 text-left">Fecha Pago</th>
                    <th class="px-4 py-2 text-left">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $payment->installment_number }}</td>
                        <td class="px-4 py-2">${{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-2">{{ $payment->due_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $payment->paid_date ? $payment->paid_date->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-2">
                            <x-filament::badge
                                :color="$payment->status === 'paid' ? 'success' : 'warning'"
                            >
                                {{ $payment->status === 'paid' ? 'Pagado' : 'Pendiente' }}
                            </x-filament::badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>
