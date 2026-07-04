<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Pending Orders', Order::where('status', Order::STATUS_PENDING)->count())
                ->description('Awaiting packing assignment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-o-inbox'),

            Stat::make('Currently Packing', Order::where('status', Order::STATUS_PACKING)->count())
                ->description('Active packing in progress')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->icon('heroicon-o-cube'),

            Stat::make('Packed', Order::where('status', Order::STATUS_PACKED)->count())
                ->description('Ready for admin review')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->icon('heroicon-o-archive-box'),

            Stat::make('Ready to Ship', Order::where('status', Order::STATUS_READY_TO_SHIP)->count())
                ->description('Approved — awaiting pickup')
                ->descriptionIcon('heroicon-m-truck')
                ->color('success')
                ->icon('heroicon-o-truck'),
        ];
    }
}
