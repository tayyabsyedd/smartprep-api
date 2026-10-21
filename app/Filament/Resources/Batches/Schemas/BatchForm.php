<?php

namespace App\Filament\Resources\Batches\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('subject_id')
                    ->numeric(),
                TextInput::make('session_time')
                    ->required()
                    ->default('evening'),
                TextInput::make('mode')
                    ->required()
                    ->default('online'),
                TextInput::make('location'),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at'),
                TextInput::make('max_seats')
                    ->numeric(),
                Textarea::make('challan_info')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('scheduled'),
            ]);
    }
}
