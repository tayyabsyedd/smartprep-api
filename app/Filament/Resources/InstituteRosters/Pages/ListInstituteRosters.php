<?php

namespace App\Filament\Resources\InstituteRosters\Pages;

use App\Filament\Resources\InstituteRosters\InstituteRosterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstituteRosters extends ListRecords
{
    protected static string $resource = InstituteRosterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
