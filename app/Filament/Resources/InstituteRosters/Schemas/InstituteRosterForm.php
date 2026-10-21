<?php

namespace App\Filament\Resources\InstituteRosters\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InstituteRosterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('enrollment_id')
                    ->required(),
                TextInput::make('student_name'),
                TextInput::make('program'),
                TextInput::make('batch'),
                Toggle::make('is_claimed')
                    ->required(),
                TextInput::make('claimed_by')
                    ->numeric(),
            ]);
    }
}
