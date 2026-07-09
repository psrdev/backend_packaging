<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ProductResource extends Resource
{
    public static function getModel(): string
    {
        return Product::class;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationLabel(): string
    {
        return 'Products';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationGroup(): string
    {
        return 'Catalog';
    }

    protected static ?string $recordTitleAttribute = 'name';

    // ──────────────────────────────────────────────────────────────────────────
    // FORM
    // ──────────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Details')
                    ->description('Core product information used in packing.')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Product Image')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('4:3')
                            ->panelLayout('integrated')
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        TextInput::make('sku')
                            ->label('SKU')
                            ->placeholder('e.g. PROD-001')
                            ->unique(Product::class, 'sku', ignoreRecord: true)
                            ->maxLength(100),

                        TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('platform_product_id')
                            ->label('Platform Product ID')
                            ->placeholder('External product ID (optional)')
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Packing Information')
                    ->schema([
                        Textarea::make('packing_notes')
                            ->label('Packing Notes')
                            ->placeholder('Special instructions for packers (e.g. wrap individually, add desiccant)…')
                            ->rows(4)
                            ->columnSpanFull(),

                        Toggle::make('is_fragile')
                            ->label('Fragile Item')
                            ->helperText('Mark this if the product requires careful handling.')
                            ->onColor('danger')
                            ->offColor('gray')
                            ->inline(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TABLE
    // ──────────────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->size(56)
                    ->defaultImageUrl(fn (): string => 'https://ui-avatars.com/api/?name=P&background=6366f1&color=fff&size=56')
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('platform_product_id')
                    ->label('Platform ID')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_fragile')
                    ->label('Fragile')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_fragile')
                    ->label('Fragile Items')
                    ->trueLabel('Fragile only')
                    ->falseLabel('Non-fragile only')
                    ->placeholder('All products'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Product deleted')
                            ->body('The product has been permanently removed.')
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateHeading('No Products Yet')
            ->emptyStateDescription('Add your first product to start managing your catalog.')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PAGES
    // ──────────────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
