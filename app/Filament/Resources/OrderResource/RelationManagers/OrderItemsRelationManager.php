<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Order Items';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-list-bullet';

    // ──────────────────────────────────────────────────────────────────────────
    // FORM
    // ──────────────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->options(
                        Product::orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Product $p) => [
                                $p->id => "{$p->name}" . ($p->sku ? " ({$p->sku})" : ''),
                            ])
                    )
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if ($state) {
                            $product = Product::find($state);
                            $set('product_name', $product?->name ?? '');
                            $set('sku', $product?->sku ?? '');
                        }
                    })
                    ->columnSpanFull(),

                TextInput::make('product_name')
                    ->label('Product Name (snapshot)')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Auto-filled from product. Can be overridden.'),

                TextInput::make('sku')
                    ->label('SKU (snapshot)')
                    ->maxLength(100)
                    ->helperText('Auto-filled from product.'),

                TextInput::make('quantity_required')
                    ->label('Quantity Required')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1),

                TextInput::make('quantity_confirmed')
                    ->label('Quantity Confirmed')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('Updated by the packer via the mobile app.'),

                Textarea::make('packer_note')
                    ->label('Packer Note')
                    ->rows(2)
                    ->placeholder('Optional note from the packer…')
                    ->columnSpanFull(),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TABLE
    // ──────────────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\ImageColumn::make('product.image')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(44)
                    ->defaultImageUrl(fn (): string => 'https://ui-avatars.com/api/?name=P&background=6366f1&color=fff&size=44'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->weight('semibold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('quantity_required')
                    ->label('Required')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('quantity_confirmed')
                    ->label('Confirmed')
                    ->alignCenter()
                    ->color(fn ($record): string => $record->quantity_confirmed >= $record->quantity_required ? 'success' : 'danger'),

                Tables\Columns\IconColumn::make('is_confirmed')
                    ->label('Done')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('packer_note')
                    ->label('Packer Note')
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn ($record): ?string => $record->packer_note),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Item')
                    ->icon('heroicon-o-plus')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Item added')
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Item updated')
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Item removed')
                    ),
            ])
            ->emptyStateHeading('No Items Added')
            ->emptyStateDescription('Add products to this order.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
