<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Set;

class OrderResource extends Resource
{
    public static function getModel(): string
    {
        return Order::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-shopping-cart';
    }

    public static function getNavigationLabel(): string
    {
        return 'Orders';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationGroup(): string
    {
        return 'Orders';
    }

    protected static ?string $recordTitleAttribute = 'order_number';

    // ──────────────────────────────────────────────────────────────────────────
    // SHARED COLOR MAPS
    // ──────────────────────────────────────────────────────────────────────────

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'pending'       => 'warning',
            'packing'       => 'info',
            'packed'        => 'primary',
            'ready_to_ship' => 'success',
            'shipped'       => 'success',
            'issue'         => 'danger',
            'cancelled'     => 'gray',
            default         => 'gray',
        };
    }

    public static function priorityColor(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'danger',
            'high'   => 'warning',
            default  => 'gray',
        };
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM
    // ──────────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Details')
                    ->description('Core order information.')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Order Number')
                            ->required()
                            ->unique(Order::class, 'order_number', ignoreRecord: true)
                            ->placeholder('e.g. AMZ-2024-001')
                            ->maxLength(100),

                        Select::make('platform')
                            ->label('Platform')
                            ->options([
                                'amazon'      => 'Amazon',
                                'meesho'      => 'Meesho',
                                'flipkart'    => 'Flipkart',
                                'woocommerce' => 'WooCommerce',
                                'whatsapp'    => 'WhatsApp',
                                'manual'      => 'Manual',
                            ])
                            ->searchable()
                            ->placeholder('Select platform'),

                        Select::make('priority')
                            ->label('Priority')
                            ->options(Order::priorities())
                            ->default(Order::PRIORITY_NORMAL)
                            ->required(),

                        Select::make('status')
                            ->label('Status')
                            ->options(Order::statuses())
                            ->default(Order::STATUS_PENDING)
                            ->required()
                            ->disabledOn('create'),
                    ])
                    ->columns(2),

                Section::make('Customer Information')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->maxLength(255),

                        TextInput::make('customer_phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20),

                        Textarea::make('shipping_address')
                            ->label('Shipping Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        DateTimePicker::make('pickup_deadline')
                            ->label('Pickup Deadline')
                            ->seconds(false),

                        FileUpload::make('shipping_label')
                            ->label('Shipping Label')
                            ->image()
                            ->disk('public')
                            ->directory('shipping-labels')
                            ->imagePreviewHeight('120')
                            ->maxSize(5120),
                    ])
                    ->columns(2),


                Section::make('Order Items')
                    ->description('Add products and quantities required for this order.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Product Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('sku')
                                            ->label('SKU')
                                            ->placeholder('e.g. PROD-001')
                                            ->unique(Product::class, 'sku', ignoreRecord: true)
                                            ->maxLength(100),
                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $product = Product::create([
                                            'name' => $data['name'],
                                            'sku' => $data['sku'] ?? null,
                                        ]);
                                        return $product->id;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (?int $state, Set $set): void {
                                        if ($state) {
                                            $product = Product::find($state);
                                            $set('product_name', $product?->name ?? '');
                                            $set('sku', $product?->sku ?? '');
                                        }
                                    }),

                                TextInput::make('product_name')
                                    ->label('Product Name (snapshot)')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Auto-filled from product.'),

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
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->defaultItems(1),
                    ]),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TABLE
    // ──────────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Order number copied'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '—')),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Order::priorities()[$state] ?? $state)
                    ->color(fn (string $state): string => static::priorityColor($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Order::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => static::statusColor($state)),

                Tables\Columns\TextColumn::make('packer.name')
                    ->label('Packer')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pickup_deadline')
                    ->label('Deadline')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Order::statuses())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'amazon'      => 'Amazon',
                        'meesho'      => 'Meesho',
                        'flipkart'    => 'Flipkart',
                        'woocommerce' => 'WooCommerce',
                        'whatsapp'    => 'WhatsApp',
                        'manual'      => 'Manual',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(Order::priorities())
                    ->multiple(),

                Tables\Filters\Filter::make('has_issue')
                    ->label('Issues Only')
                    ->query(fn (Builder $query): Builder => $query->where('status', Order::STATUS_ISSUE))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                // ── Mark Ready to Ship ──────────────────────────────────────
                Action::make('mark_ready_to_ship')
                    ->label('Mark Ready to Ship')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->isPacked())
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Ready to Ship?')
                    ->modalDescription('Confirm that all items are packed and verified. This cannot be undone.')
                    ->modalSubmitActionLabel('Confirm')
                    ->action(function (Order $record): void {
                        $old = $record->status;
                        $record->update([
                            'status'           => Order::STATUS_READY_TO_SHIP,
                            'ready_to_ship_at' => now(),
                        ]);
                        $record->logStatus($old, Order::STATUS_READY_TO_SHIP, auth()->id(), 'Marked Ready to Ship by admin.');

                        Notification::make()
                            ->success()
                            ->title('Order ready to ship')
                            ->body("Order {$record->order_number} is now ready for pickup.")
                            ->send();
                    }),

                // ── Mark Shipped ────────────────────────────────────────────
                Action::make('mark_shipped')
                    ->label('Mark Shipped')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->isReadyToShip())
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Shipment?')
                    ->modalDescription('Mark this order as shipped. The shipped timestamp will be recorded.')
                    ->modalSubmitActionLabel('Mark Shipped')
                    ->action(function (Order $record): void {
                        $old = $record->status;
                        $record->update([
                            'status'     => Order::STATUS_SHIPPED,
                            'shipped_at' => now(),
                        ]);
                        $record->logStatus($old, Order::STATUS_SHIPPED, auth()->id(), 'Marked Shipped by admin.');

                        Notification::make()
                            ->success()
                            ->title('Order shipped')
                            ->body("Order {$record->order_number} has been dispatched.")
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->emptyStateHeading('No Orders Found')
            ->emptyStateDescription('Create your first order to start tracking packing.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // INFOLIST
    // ──────────────────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Overview')
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Order Number')
                            ->weight(FontWeight::Bold)
                            ->copyable(),

                        TextEntry::make('platform')
                            ->label('Platform')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '—')),

                        TextEntry::make('priority')
                            ->label('Priority')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => Order::priorities()[$state] ?? $state)
                            ->color(fn (string $state): string => static::priorityColor($state)),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => Order::statuses()[$state] ?? $state)
                            ->color(fn (string $state): string => static::statusColor($state)),
                    ])
                    ->columns(4),

                Section::make('Customer')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Name'),

                        TextEntry::make('customer_phone')
                            ->label('Phone')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('shipping_address')
                            ->label('Address')
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('pickup_deadline')
                            ->label('Pickup Deadline')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('Not set'),
                    ])
                    ->columns(3),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product_name')
                                    ->label('Product Name')
                                    ->weight(FontWeight::Medium),
                                TextEntry::make('sku')
                                    ->label('SKU')
                                    ->placeholder('—'),
                                TextEntry::make('quantity_required')
                                    ->label('Qty Required')
                                    ->numeric(),
                                TextEntry::make('quantity_confirmed')
                                    ->label('Qty Packed')
                                    ->numeric(),
                                IconEntry::make('is_confirmed')
                                    ->label('Fully Packed?')
                                    ->boolean(),
                                TextEntry::make('packer_note')
                                    ->label('Packer Note')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Shipping Label')
                    ->schema([
                        ImageEntry::make('shipping_label')
                            ->label('')
                            ->disk('public')
                            ->height(200)
                            ->placeholder('No label uploaded'),
                    ])
                    ->collapsed()
                    ->collapsible(),

                Section::make('Assignment & Timestamps')
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label('Created By')
                            ->placeholder('—'),

                        TextEntry::make('packer.name')
                            ->label('Packer')
                            ->placeholder('Unassigned'),

                        TextEntry::make('packing_started_at')
                            ->label('Packing Started')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('packed_at')
                            ->label('Packed At')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('ready_to_ship_at')
                            ->label('Ready to Ship At')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),

                        TextEntry::make('shipped_at')
                            ->label('Shipped At')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—'),
                    ])
                    ->columns(3),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RELATION MANAGERS
    // ──────────────────────────────────────────────────────────────────────────

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\OrderItemsRelationManager::class,
            RelationManagers\PackingPhotosRelationManager::class,
            RelationManagers\StatusLogsRelationManager::class,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PAGES
    // ──────────────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit'   => Pages\EditOrder::route('/{record}/edit'),
            'view'   => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
