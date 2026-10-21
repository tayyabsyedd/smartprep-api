<?php

namespace App\Filament\Resources\EnrollmentRequests\Pages;

use App\Filament\Resources\EnrollmentRequests\EnrollmentRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEnrollmentRequests extends ListRecords
{
    protected static string $resource = EnrollmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
