<div class="fi-ta">
    <div class="overflow-x-auto">
        <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
            <thead>
                <tr class="fi-ta-header">
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">N° Cuota</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Monto</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Fecha Vencimiento</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Fecha Pago</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($payments as $payment)
                    <tr class="fi-ta-row">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ $payment->installment_number }}
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            ${{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ $payment->due_date->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3">
                            @if($payment->paid_date)
                                <span class="text-success-600 dark:text-success-400">
                                    {{ $payment->paid_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div @class([
                                'inline-flex items-center justify-center gap-x-1 rounded-lg px-2 py-1 text-xs font-medium',
                                'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400' => $payment->status === 'paid',
                                'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400' => $payment->status === 'pending',
                            ])>
                                @if($payment->status === 'paid')
                                    <x-heroicon-s-check-circle class="w-4 h-4"/>
                                @else
                                    <x-heroicon-s-clock class="w-4 h-4"/>
                                @endif
                                {{ $payment->status === 'paid' ? 'Pagado' : 'Pendiente' }}
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
