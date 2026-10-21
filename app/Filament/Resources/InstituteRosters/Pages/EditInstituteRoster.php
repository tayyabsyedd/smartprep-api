<?php

namespace App\Filament\Resources\InstituteRosters\Pages;

use App\Filament\Resources\InstituteRosters\InstituteRosterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstituteRoster extends EditRecord
{
    protected static string $resource = InstituteRosterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
