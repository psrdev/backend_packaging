<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ShipmentStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today();

        return [
            Stat::make('Shipped Today', Order::where('status', Order::STATUS_SHIPPED)
                ->whereDate('shipped_at', $today)
                ->count())
                ->description('Orders dispatched today')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success')
                ->icon('heroicon-o-paper-airplane'),

            Stat::make('Issues', Order::where('status', Order::STATUS_ISSUE)->count())
                ->description('Orders flagged with problems')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make("Today's Orders", Order::whereDate('created_at', $today)->count())
                ->description('New orders received today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Stat::make('Total Products', Product::count())
                ->description('Products in catalog')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('gray')
                ->icon('heroicon-o-squares-2x2'),
        ];
    }
}
