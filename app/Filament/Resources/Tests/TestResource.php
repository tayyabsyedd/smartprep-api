<?php

namespace App\Filament\Resources\Tests;

use App\Filament\Resources\Tests\Pages\CreateTest;
use App\Filament\Resources\Tests\Pages\EditTest;
use App\Filament\Resources\Tests\Pages\ListTests;
use App\Filament\Resources\Tests\Schemas\TestForm;
use App\Filament\Resources\Tests\Tables\TestsTable;
use App\Models\Test;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TestResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestsTable::configure($table);
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
            'index' => ListTests::route('/'),
            'create' => CreateTest::route('/create'),
            'edit' => EditTest::route('/{record}/edit'),
        ];
    }
}
