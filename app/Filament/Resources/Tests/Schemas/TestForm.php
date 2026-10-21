<?php

namespace App\Filament\Resources\Tests\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('module_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('kind')
                    ->required()
                    ->default('practice'),
                Toggle::make('timed')
                    ->required(),
                TextInput::make('duration_minutes')
                    ->numeric(),
                Toggle::make('is_free')
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
            ]);
    }
}
