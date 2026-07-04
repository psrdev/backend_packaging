<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    // ──────────────────────────────────────────────────────────────────────────
    // HEADER ACTIONS
    // ──────────────────────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('mark_ready_to_ship')
                ->label('Mark Ready to Ship')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record->isPacked())
                ->requiresConfirmation()
                ->modalHeading('Mark as Ready to Ship?')
                ->modalDescription('Confirm that all items are packed and verified.')
                ->modalSubmitActionLabel('Confirm')
                ->action(function (): void {
                    $old = $this->record->status;
                    $this->record->update([
                        'status'           => Order::STATUS_READY_TO_SHIP,
                        'ready_to_ship_at' => now(),
                    ]);
                    $this->record->logStatus($old, Order::STATUS_READY_TO_SHIP, auth()->id(), 'Marked Ready to Ship by admin.');
                    $this->refreshFormData(['status', 'ready_to_ship_at']);

                    Notification::make()
                        ->success()
                        ->title('Order ready to ship')
                        ->body("Order {$this->record->order_number} is approved for pickup.")
                        ->send();
                }),

            Actions\Action::make('mark_shipped')
                ->label('Mark Shipped')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (): bool => $this->record->isReadyToShip())
                ->requiresConfirmation()
                ->modalHeading('Confirm Shipment?')
                ->modalDescription('Record that this order has been dispatched.')
                ->modalSubmitActionLabel('Mark Shipped')
                ->action(function (): void {
                    $old = $this->record->status;
                    $this->record->update([
                        'status'     => Order::STATUS_SHIPPED,
                        'shipped_at' => now(),
                    ]);
                    $this->record->logStatus($old, Order::STATUS_SHIPPED, auth()->id(), 'Marked Shipped by admin.');
                    $this->refreshFormData(['status', 'shipped_at']);

                    Notification::make()
                        ->success()
                        ->title('Order shipped')
                        ->body("Order {$this->record->order_number} has been dispatched.")
                        ->send();
                }),
        ];
    }
}
