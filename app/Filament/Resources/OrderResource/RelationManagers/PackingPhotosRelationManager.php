<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;

class PackingPhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Packing Photos';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-camera';

    // ──────────────────────────────────────────────────────────────────────────
    // FORM
    // ──────────────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('photo_path')
                    ->label('Photo')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('packing-photos')
                    ->imagePreviewHeight('200')
                    ->maxSize(10240)
                    ->columnSpanFull(),

                Textarea::make('note')
                    ->label('Note')
                    ->placeholder('Optional note about this photo…')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TABLE
    // ──────────────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('photo_path')
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->height(80)
                    ->width(120)
                    ->extraImgAttributes([
                        'class' => 'rounded-lg object-cover cursor-pointer',
                    ]),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('—')
                    ->limit(60),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Photo')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by'] = auth()->id();
                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Photo uploaded')
                    ),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn ($record): string => asset('storage/' . $record->photo_path), shouldOpenInNewTab: true),

                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Photo deleted')
                    ),
            ])
            ->emptyStateHeading('No Photos Yet')
            ->emptyStateDescription('No packing photos have been uploaded for this order.')
            ->emptyStateIcon('heroicon-o-camera');
    }
}
