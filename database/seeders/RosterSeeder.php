<?php

namespace Database\Seeders;

use App\Models\InstituteRoster;
use Illuminate\Database\Seeder;

class RosterSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['enrollment_id' => 'ROLL-100', 'student_name' => 'Demo Enrolled', 'program' => 'MDCAT', 'batch' => '2026-A'],
            ['enrollment_id' => 'ROLL-101', 'student_name' => 'Ali Raza', 'program' => 'MDCAT', 'batch' => '2026-A'],
            ['enrollment_id' => 'ROLL-102', 'student_name' => 'Sara Khan', 'program' => 'ECAT', 'batch' => '2026-B'],
            ['enrollment_id' => 'ROLL-103', 'student_name' => 'Usman Tariq', 'program' => 'MDCAT', 'batch' => '2026-A'],
        ];

        foreach ($rows as $row) {
            InstituteRoster::firstOrCreate(['enrollment_id' => $row['enrollment_id']], $row);
        }
    }
}
