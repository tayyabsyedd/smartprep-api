<?php

namespace App\Filament\Resources\Tutors;

use App\Filament\Resources\Tutors\Pages\CreateTutor;
use App\Filament\Resources\Tutors\Pages\EditTutor;
use App\Filament\Resources\Tutors\Pages\ListTutors;
use App\Filament\Resources\Tutors\Schemas\TutorForm;
use App\Filament\Resources\Tutors\Tables\TutorsTable;
use App\Models\Tutor;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TutorResource extends Resource
{
    protected static ?string $model = Tutor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Scheduling';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return TutorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TutorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTutors::route('/'),
            'create' => CreateTutor::route('/create'),
            'edit' => EditTutor::route('/{record}/edit'),
        ];
    }
}
