<?php

namespace App\Filament\Resources\TutorSlots\Pages;

use App\Filament\Resources\TutorSlots\TutorSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTutorSlots extends ListRecords
{
    protected static string $resource = TutorSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
