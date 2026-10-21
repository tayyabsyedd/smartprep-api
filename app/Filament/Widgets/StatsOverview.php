<?php

namespace App\Filament\Widgets;

use App\Models\EnrollmentRequest;
use App\Models\Purchase;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Students', User::where('role', 'student')->count()),
            Stat::make('Pending enrollments', EnrollmentRequest::where('status', 'pending')->count()),
            Stat::make('Slips awaiting verification', Purchase::where('status', 'pending_verification')->count()),
            Stat::make('Revenue (completed)', 'Rs '.number_format((float) Purchase::where('status', 'completed')->sum('amount'))),
        ];
    }
}
