<?php

namespace App\Filament\Resources\TutorSlots;

use App\Filament\Resources\TutorSlots\Pages\CreateTutorSlot;
use App\Filament\Resources\TutorSlots\Pages\EditTutorSlot;
use App\Filament\Resources\TutorSlots\Pages\ListTutorSlots;
use App\Filament\Resources\TutorSlots\Schemas\TutorSlotForm;
use App\Filament\Resources\TutorSlots\Tables\TutorSlotsTable;
use App\Models\TutorSlot;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TutorSlotResource extends Resource
{
    protected static ?string $model = TutorSlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Scheduling';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TutorSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TutorSlotsTable::configure($table);
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
            'index' => ListTutorSlots::route('/'),
            'create' => CreateTutorSlot::route('/create'),
            'edit' => EditTutorSlot::route('/{record}/edit'),
        ];
    }
}
