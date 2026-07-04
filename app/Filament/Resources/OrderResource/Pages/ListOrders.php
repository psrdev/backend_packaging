<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Order')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Order::count()),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_PENDING))
                ->badge(Order::where('status', Order::STATUS_PENDING)->count())
                ->badgeColor('warning'),

            'packing' => Tab::make('Packing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_PACKING))
                ->badge(Order::where('status', Order::STATUS_PACKING)->count())
                ->badgeColor('info'),

            'packed' => Tab::make('Packed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_PACKED))
                ->badge(Order::where('status', Order::STATUS_PACKED)->count())
                ->badgeColor('primary'),

            'ready_to_ship' => Tab::make('Ready to Ship')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_READY_TO_SHIP))
                ->badge(Order::where('status', Order::STATUS_READY_TO_SHIP)->count())
                ->badgeColor('success'),

            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_SHIPPED))
                ->badge(Order::where('status', Order::STATUS_SHIPPED)->count())
                ->badgeColor('success'),

            'issue' => Tab::make('Issues')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Order::STATUS_ISSUE))
                ->badge(Order::where('status', Order::STATUS_ISSUE)->count())
                ->badgeColor('danger'),
        ];
    }
}
