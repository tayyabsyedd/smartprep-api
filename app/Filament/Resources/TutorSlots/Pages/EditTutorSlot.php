<?php

namespace App\Filament\Resources\TutorSlots\Pages;

use App\Filament\Resources\TutorSlots\TutorSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTutorSlot extends EditRecord
{
    protected static string $resource = TutorSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
