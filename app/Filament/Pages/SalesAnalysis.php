<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Components\ViewComponent;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Exports\CreditSalesExport;
use App\Exports\CashSalesExport;
use App\Models\SaleDetail;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use App\Exports\SalesAnalyticsExport;
use App\Exports\OverdueCreditsExport;
use App\Exports\InstallmentsExport;
use App\Exports\ProductSalesExport;
use App\Exports\ProfitsExport;
use Filament\Forms\Get;
use Filament\Forms\Set;

class SalesAnalysis extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Análisis de Ventas';
    protected static ?string $title = 'Análisis de Ventas';
    protected static ?string $navigationGroup = 'Reportes';

    // Agregar las propiedades protegidas para las fechas
    protected ?\DateTime $startDate = null;
    protected ?\DateTime $endDate = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateReport')
                ->label('Generar Reporte')
                ->modalSubmitActionLabel('Generar')
                ->modalIcon('heroicon-m-document-arrow-down')
                ->form([
                    Select::make('reportType')
                        ->label('Tipo de Reporte')
                        ->options([
                            'General' => [
                                'sales' => 'Ventas Generales',
                                'credits' => 'Créditos',
                                'cash_sales' => 'Ventas de Contado',
                            ],
                            'Seguimiento' => [
                                'overdue_credits' => '🔴 Créditos con Mora',
                                'pending_credits' => '⚠️ Créditos Activos',
                                'overdue_installments' => '⏰ Cuotas Vencidas',
                            ],
                            'Analisis' => [
                                'products' => '📊 Productos más Vendidos',
                                'profits' => '💰 Ganancias y Márgenes',
                                'installments' => '📅 Cuotas y Pagos',
                            ]
                        ])
                        ->required()
                        ->helperText(function ($state) {
                            return match($state) {
                                'overdue_credits' => 'Créditos en mora: ' . 
                                    Sale::whereHas('installments', fn($q) => $q->where('status', 'overdue'))->count(),
                                'pending_credits' => 'Créditos activos: ' . 
                                    Sale::where('payment_type', 'credit')->where('status', 'pending')->count(),
                                'overdue_installments' => 'Cuotas vencidas: ' . 
                                    \App\Models\Installment::where('status', 'overdue')->count(),
                                default => 'Selecciona el tipo de reporte que necesitas generar'
                            };
                        }),
                        
                    Select::make('dateRange')
                        ->label('Período')
                        ->options([
                            'daily' => 'Día Actual',
                            'weekly' => 'Semana Actual',
                            'monthly' => 'Mes Actual',
                            'quarterly' => 'Trimestre Actual',
                            'semester' => 'Semestre Actual',
                            'yearly' => 'Año Actual',
                            'custom' => 'Personalizado',
                        ])
                        ->default('monthly')
                        ->required()
                        ->reactive(),
                        
                    DatePicker::make('startDate')
                        ->label('Fecha Inicio')
                        ->visible(fn (Get $get): bool => $get('dateRange') === 'custom')
                        ->required(fn (Get $get): bool => $get('dateRange') === 'custom'),
                        
                    DatePicker::make('endDate')
                        ->label('Fecha Fin')
                        ->visible(fn (Get $get): bool => $get('dateRange') === 'custom')
                        ->required(fn (Get $get): bool => $get('dateRange') === 'custom'),
                ])
                ->action(function (array $data) {
                    try {
                        if ($data['dateRange'] === 'custom') {
                            $startDate = \Carbon\Carbon::parse($data['startDate']);
                            $endDate = \Carbon\Carbon::parse($data['endDate']);
                            
                            if ($endDate->isBefore($startDate)) {
                                Notification::make()
                                    ->title('Error en las fechas')
                                    ->body('La fecha fin no puede ser anterior a la fecha inicio')
                                    ->danger()
                                    ->send();
                                    
                                $this->halt();
                                return;
                            }
                        }
                        
                        $dates = $this->calculateDates($data['dateRange']);
                        $filename = $this->generateFilename($data);
                        
                        return match($data['reportType']) {
                            'sales' => Excel::download(new SalesAnalyticsExport($dates[0], $dates[1]), $filename),
                            'credits' => Excel::download(new CreditSalesExport($dates[0], $dates[1]), $filename),
                            'cash_sales' => Excel::download(new CashSalesExport($dates[0], $dates[1]), $filename),
                            'overdue_credits' => Excel::download(new OverdueCreditsExport(), $filename),
                            'pending_credits' => Excel::download(new CreditSalesExport($dates[0], $dates[1]), $filename),
                            'installments' => Excel::download(new InstallmentsExport($dates[0], $dates[1]), $filename),
                            'products' => Excel::download(new ProductSalesExport($dates[0], $dates[1]), $filename),
                            'profits' => Excel::download(new ProfitsExport($dates[0], $dates[1]), $filename),
                            'overdue_installments' => Excel::download(new InstallmentsExport($dates[0], $dates[1]), $filename),
                            default => throw new \Exception('Tipo de reporte no válido'),
                        };
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al generar el reporte')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    private function calculateDates(string $range): array
    {
        if ($range === 'custom') {
            return [
                $this->startDate ?? now()->startOfMonth(),
                $this->endDate ?? now()->endOfMonth(),
            ];
        }

        return match($range) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarterly' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'semester' => [now()->month <= 6 ? 
                now()->startOfYear() : now()->startOfYear()->addMonths(6),
                now()->month <= 6 ? 
                now()->startOfYear()->addMonths(6)->subDay() : now()->endOfYear()
            ],
            'yearly' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    private function generateFilename(array $data): string
    {
        $type = match($data['reportType']) {
            'sales' => 'ventas-generales',
            'credits' => 'creditos',
            'cash_sales' => 'ventas-contado',
            'pending_credits' => 'creditos-pendientes',
            'overdue_credits' => 'creditos-vencidos',
            'installments' => 'cuotas',
            'overdue_installments' => 'cuotas-vencidas',
            'products' => 'productos',
            'profits' => 'ganancias',
        };

        if ($data['dateRange'] === 'custom') {
            $startDate = \Carbon\Carbon::parse($data['startDate']);
            $endDate = \Carbon\Carbon::parse($data['endDate']);
            $period = $startDate->format('Y-m-d') . '-a-' . $endDate->format('Y-m-d');
        } else {
            $period = match($data['dateRange']) {
                'daily' => now()->format('Y-m-d'),
                'weekly' => 'semana-' . now()->weekOfYear,
                'monthly' => now()->format('Y-m'),
                'quarterly' => 'trimestre-' . now()->quarter . '-' . now()->year,
                'semester' => 'semestre-' . (now()->month <= 6 ? '1' : '2') . '-' . now()->year,
                'yearly' => (string)now()->year,
                default => now()->format('Y-m-d'),
            };
        }

        return "{$type}-{$period}.xlsx";
    }

    protected function getStats(): array
    {
        $totalSales = SaleDetail::sum(DB::raw('quantity * unit_price'));
        $totalCost = SaleDetail::sum(DB::raw('quantity * purchase_price'));
        $totalProfit = $totalSales - $totalCost;
        $profitMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;

        return [
            Card::make('Ventas Totales', '$ ' . number_format($totalSales, 0, ',', '.'))
                ->description('Total facturado')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Card::make('Ganancia Total', '$ ' . number_format($totalProfit, 0, ',', '.'))
                ->description('Margen bruto')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Card::make('Margen Promedio', number_format($profitMargin, 1) . '%')
                ->description('Rentabilidad')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SaleDetail::query()
                    ->select([
                        'id',
                        'product_name',
                        DB::raw('SUM(quantity) as total_quantity'),
                        DB::raw('SUM(quantity * unit_price) as total_sales'),
                        DB::raw('SUM(quantity * (unit_price - purchase_price)) as total_profit')
                    ])
                    ->groupBy('id', 'product_name')
                    ->orderByDesc('total_quantity')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('total_sales')
                    ->label('Ventas')
                    ->money('COP')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('total_profit')
                    ->label('Ganancia')
                    ->money('COP')
                    ->sortable()
                    ->alignEnd()
                    ->color('success'),
            ])
            ->recordUrl(null)
            ->paginated(false);
    }

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->id;
    }

    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'topProductsTable' => $this->table
        ];
    }

    protected static string $view = 'filament.pages.sales-analysis';
}
