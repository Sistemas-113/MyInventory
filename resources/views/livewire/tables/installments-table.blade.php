<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-3">N° Cuota</th>
                    <th scope="col" class="px-4 py-3">Monto</th>
                    <th scope="col" class="px-4 py-3">Fecha Vencimiento</th>
                    <th scope="col" class="px-4 py-3">Estado</th>
                    <th scope="col" class="px-4 py-3">Fecha Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($installments as $installment)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $installment->installment_number }}
                        </td>
                        <td class="px-4 py-2">
                            $ {{ number_format($installment->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $installment->due_date->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-2">
                            @php
                                $statusColor = match($installment->status) {
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                };
                                $statusLabel = match($installment->status) {
                                    'pending' => 'Pendiente',
                                    'paid' => 'Pagada',
                                    'overdue' => 'Vencida',
                                };
                                $statusIcon = match($installment->status) {
                                    'pending' => '⚠️',
                                    'paid' => '✅',
                                    'overdue' => '❌',
                                };
                            @endphp
                            <span class="px-2 py-1 text-{{ $statusColor }}-500">
                                {{ $statusIcon }} {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            {{ $installment->paid_date ? $installment->paid_date->format('d/m/Y') : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
