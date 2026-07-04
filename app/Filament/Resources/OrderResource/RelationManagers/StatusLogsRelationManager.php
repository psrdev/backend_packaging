<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'statusLogs';

    protected static ?string $title = 'Status History';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-clock';

    // Read-only — no creating or editing from admin
    protected bool $isReadOnly = true;

    // ──────────────────────────────────────────────────────────────────────────
    // FORM (stub — required by interface even though we disable create/edit)
    // ──────────────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_status')
                    ->label('From Status')
                    ->options(Order::statuses()),

                Select::make('to_status')
                    ->label('To Status')
                    ->options(Order::statuses())
                    ->required(),

                Textarea::make('note')
                    ->label('Note')
                    ->columnSpanFull(),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TABLE
    // ──────────────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('to_status')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('from_status')
                    ->label('From')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Order::statuses()[$state] ?? $state) : '—')
                    ->color(fn (?string $state): string => $state ? OrderResource::statusColor($state) : 'gray'),

                Tables\Columns\TextColumn::make('to_status')
                    ->label('To')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Order::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => OrderResource::statusColor($state)),

                Tables\Columns\TextColumn::make('changedBy.name')
                    ->label('Changed By')
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn ($record): ?string => $record->note),
            ])
            ->headerActions([])  // No create
            ->actions([])        // No edit / delete
            ->emptyStateHeading('No Status Changes')
            ->emptyStateDescription('Status transitions will appear here as the order progresses.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
