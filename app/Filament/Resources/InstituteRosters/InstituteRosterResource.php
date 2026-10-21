<?php

namespace App\Filament\Resources\InstituteRosters;

use App\Filament\Resources\InstituteRosters\Pages\CreateInstituteRoster;
use App\Filament\Resources\InstituteRosters\Pages\EditInstituteRoster;
use App\Filament\Resources\InstituteRosters\Pages\ListInstituteRosters;
use App\Filament\Resources\InstituteRosters\Schemas\InstituteRosterForm;
use App\Filament\Resources\InstituteRosters\Tables\InstituteRostersTable;
use App\Models\InstituteRoster;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstituteRosterResource extends Resource
{
    protected static ?string $model = InstituteRoster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return InstituteRosterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstituteRostersTable::configure($table);
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
            'index' => ListInstituteRosters::route('/'),
            'create' => CreateInstituteRoster::route('/create'),
            'edit' => EditInstituteRoster::route('/{record}/edit'),
        ];
    }
}
