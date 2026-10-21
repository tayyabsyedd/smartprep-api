<?php

namespace App\Filament\Resources\EnrollmentRequests;

use App\Filament\Resources\EnrollmentRequests\Pages\ListEnrollmentRequests;
use App\Filament\Resources\EnrollmentRequests\Schemas\EnrollmentRequestForm;
use App\Filament\Resources\EnrollmentRequests\Tables\EnrollmentRequestsTable;
use App\Models\EnrollmentRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EnrollmentRequestResource extends Resource
{
    protected static ?string $model = EnrollmentRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Enrollment Requests';

    protected static string|UnitEnum|null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return EnrollmentRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EnrollmentRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnrollmentRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
