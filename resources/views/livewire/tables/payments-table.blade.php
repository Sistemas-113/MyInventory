<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-3">Fecha</th>
                    <th scope="col" class="px-4 py-3">Cuota</th>
                    <th scope="col" class="px-4 py-3">Monto</th>
                    <th scope="col" class="px-4 py-3">Método</th>
                    <th scope="col" class="px-4 py-3">Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $payment->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-2">
                            Cuota {{ $payment->installment->installment_number }}
                        </td>
                        <td class="px-4 py-2 font-medium">
                            $ {{ number_format($payment->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2">
                            @php
                                $methodColor = match($payment->payment_method) {
                                    'cash' => 'success',
                                    'transfer' => 'primary',
                                    'card' => 'warning',
                                    default => 'gray'
                                };
                                $methodIcon = match($payment->payment_method) {
                                    'cash' => '💵',
                                    'transfer' => '🏦',
                                    'card' => '💳',
                                    default => '🔄'
                                };
                                $methodLabel = match($payment->payment_method) {
                                    'cash' => 'Efectivo',
                                    'transfer' => 'Transferencia',
                                    'card' => 'Tarjeta',
                                    default => 'Otro'
                                };
                            @endphp
                            <span class="px-2 py-1 text-{{ $methodColor }}-500">
                                {{ $methodIcon }} {{ $methodLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-2 max-w-xs truncate">
                            {{ $payment->notes ?: '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
