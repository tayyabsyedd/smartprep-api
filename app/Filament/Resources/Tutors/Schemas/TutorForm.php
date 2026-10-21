<?php

namespace App\Filament\Resources\Tutors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TutorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Textarea::make('bio')
                    ->columnSpanFull(),
                Textarea::make('specialties')
                    ->columnSpanFull(),
                TextInput::make('hourly_rate')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('photo_path'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
