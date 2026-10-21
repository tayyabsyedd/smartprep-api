<?php

namespace App\Filament\Resources\Purchases\Tables;

use App\Models\Purchase;
use App\Services\Payments\PaymentManager;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable(),
                TextColumn::make('purchasable_type')
                    ->label('Item type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('purchasable_id')
                    ->label('Item ID')
                    ->numeric(),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'pending_verification' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('gateway_reference')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('purchased_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'pending_verification' => 'Pending verification',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'mock' => 'Mock',
                        'jazzcash' => 'JazzCash',
                        'easypaisa' => 'Easypaisa',
                        'bank_transfer' => 'Bank transfer',
                        'play_billing' => 'Play Billing',
                    ]),
            ])
            ->recordActions([
                Action::make('viewSlip')
                    ->label('View slip')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Purchase $record): string => route('admin.slips.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Purchase $record): bool => filled($record->proof_image_path)),
                Action::make('approveSlip')
                    ->label('Approve slip')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Purchase $record): bool => $record->status === 'pending_verification')
                    ->requiresConfirmation()
                    ->modalHeading('Approve bank transfer')
                    ->modalDescription('This completes the purchase and grants the student access to the item.')
                    ->action(function (Purchase $record): void {
                        app(PaymentManager::class)->complete($record);
                    }),
                Action::make('rejectSlip')
                    ->label('Reject slip')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Purchase $record): bool => $record->status === 'pending_verification')
                    ->requiresConfirmation()
                    ->modalHeading('Reject bank transfer')
                    ->modalDescription('This marks the purchase failed. No access is granted.')
                    ->action(function (Purchase $record): void {
                        app(PaymentManager::class)->fail($record);
                    }),
            ])
            ->toolbarActions([]);
    }
}
