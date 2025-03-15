<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    protected int | string | array $columnSpan = [
        'sm' => 2,
        'md' => 3,
        'xl' => 4,
    ];

    protected function getCards(): array
    {
        return [
            // Define your cards here
        ];
    }
}