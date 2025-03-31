<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-300 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
                <tr class="bg-primary-50 dark:bg-primary-500/10">
                    <th class="px-4 py-3.5 text-left text-sm font-semibold text-primary-900 dark:text-primary-100">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-m-queue-list" class="w-5 h-5"/>
                            N° Cuota
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-right text-sm font-semibold text-primary-900 dark:text-primary-100">
                        <div class="flex items-center justify-end gap-2">
                            <x-filament::icon icon="heroicon-m-currency-dollar" class="w-5 h-5"/>
                            Valor Cuota
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-right text-sm font-semibold text-primary-900 dark:text-primary-100">
                        <div class="flex items-center justify-end gap-2">
                            <x-filament::icon icon="heroicon-m-banknotes" class="w-5 h-5"/>
                            Abonado
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-right text-sm font-semibold text-primary-900 dark:text-primary-100">
                        <div class="flex items-center justify-end gap-2">
                            <x-filament::icon icon="heroicon-m-scale" class="w-5 h-5"/>
                            Saldo
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-center text-sm font-semibold text-primary-900 dark:text-primary-100">
                        <div class="flex items-center justify-center gap-2">
                            <x-filament::icon icon="heroicon-m-calendar" class="w-5 h-5"/>
                            Vencimiento
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-center text-sm font-semibold text-primary-900 dark:text-primary-100">
                        <div class="flex items-center justify-center gap-2">
                            <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5"/>
                            Estado
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($installments as $installment)
                    <tr @class(['bg-gray-50/50 dark:bg-gray-900/25' => $loop->even])>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <span class="font-medium text-primary-600 dark:text-primary-400">
                                Cuota {{ $installment->installment_number }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                            <span class="font-semibold text-gray-900 dark:text-gray-200">
                                $ {{ number_format($installment->amount, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                            <span class="font-medium text-success-600 dark:text-success-400">
                                $ {{ number_format($installment->total_paid, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                            <span class="font-medium text-danger-600 dark:text-danger-400">
                                $ {{ number_format($installment->remaining_amount, 0, ',', '.') }}
                            </span>
                            @if($installment->payment_progress > 0 && $installment->payment_progress < 100)
                                <div class="mt-1 h-1 w-full bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500" style="width: {{ $installment->payment_progress }}%"></div>
                                </div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-center">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $installment->due_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-center">
                            <div class="flex items-center justify-end gap-2">
                                <span @class([
                                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium',
                                    'bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-400 
                                     ring-1 ring-inset ring-warning-600/20' => $installment->status === 'pending' && $installment->total_paid == 0,
                                    'bg-info-100 text-info-800 dark:bg-info-500/20 dark:text-info-400
                                     ring-1 ring-inset ring-info-600/20' => $installment->status === 'pending' && $installment->total_paid > 0,
                                    'bg-success-100 text-success-800 dark:bg-success-500/20 dark:text-success-400
                                     ring-1 ring-inset ring-success-600/20' => $installment->status === 'paid',
                                    'bg-danger-100 text-danger-800 dark:bg-danger-500/20 dark:text-danger-400
                                     ring-1 ring-inset ring-danger-600/20' => $installment->status === 'overdue',
                                ])>
                                    <x-filament::icon @class([
                                        'w-4 h-4',
                                        'text-warning-500' => $installment->status === 'pending' && $installment->total_paid == 0,
                                        'text-info-500' => $installment->status === 'pending' && $installment->total_paid > 0,
                                        'text-success-500' => $installment->status === 'paid',
                                        'text-danger-500' => $installment->status === 'overdue',
                                    ]) icon="{{ match($installment->status) {
                                        'pending' => $installment->total_paid > 0 ? 'heroicon-m-arrow-path' : 'heroicon-m-clock',
                                        'paid' => 'heroicon-m-check-circle',
                                        'overdue' => 'heroicon-m-exclamation-circle',
                                        default => 'heroicon-m-question-mark-circle'
                                    } }}" />
                                    {{ match($installment->status) {
                                        'pending' => $installment->total_paid > 0 ? 'Abono Parcial' : 'Pendiente',
                                        'paid' => 'Pagada',
                                        'overdue' => 'Vencida',
                                        default => $installment->status
                                    } }}
                                </span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
