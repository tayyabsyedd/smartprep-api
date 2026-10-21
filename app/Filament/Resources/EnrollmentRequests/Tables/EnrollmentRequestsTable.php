<?php

namespace App\Filament\Resources\EnrollmentRequests\Tables;

use App\Models\EnrollmentRequest;
use App\Services\EnrollmentReviewService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('enrollment_id')
                    ->label('Roll ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed by'),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EnrollmentRequest $record): bool => $record->status === 'pending')
                    ->schema([
                        Textarea::make('note')
                            ->label('Note (optional)'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Approve enrollment')
                    ->modalDescription('The student will be marked enrolled and gains the enrolled quota (5 free tests per module) and enrolled-only notes.')
                    ->action(function (EnrollmentRequest $record, array $data): void {
                        app(EnrollmentReviewService::class)->approve($record, auth()->user(), $data['note'] ?? null);
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EnrollmentRequest $record): bool => $record->status === 'pending')
                    ->schema([
                        Textarea::make('note')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Reject enrollment')
                    ->action(function (EnrollmentRequest $record, array $data): void {
                        app(EnrollmentReviewService::class)->reject($record, auth()->user(), $data['note'] ?? null);
                    }),
            ])
            ->toolbarActions([]);
    }
}
