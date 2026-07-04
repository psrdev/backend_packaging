<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentShipmentsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Shipments';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with(['packer'])
                    ->where('status', Order::STATUS_SHIPPED)
                    ->orderByDesc('shipped_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer'),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('packer.name')
                    ->label('Packed By')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('packed_at')
                    ->label('Packed At')
                    ->dateTime('d M H:i'),

                Tables\Columns\TextColumn::make('shipped_at')
                    ->label('Shipped At')
                    ->dateTime('d M H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
