<?php

namespace Database\Seeders;

use App\Models\Tutor;
use App\Models\TutorSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TutorSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::firstOrCreate(
            ['email' => 'tutor@smartprep.local'],
            [
                'name' => 'Demo Tutor',
                'password' => Hash::make('password'),
                'role' => 'instructor',
                'phone' => '03005556667',
            ]
        );

        $tutor = Tutor::firstOrCreate(
            ['user_id' => $instructor->id],
            [
                'bio' => 'MSc Physics, 8 years of MDCAT/ECAT preparation experience.',
                'specialties' => ['Physics', 'Mechanics', 'Electromagnetism'],
                'hourly_rate' => 2000.00,
                'rating' => 4.8,
                'is_active' => true,
            ]
        );

        if ($tutor->slots()->exists()) {
            return;
        }

        $day = now()->addDay();
        foreach ([10, 12, 16] as $hour) {
            TutorSlot::create([
                'tutor_id' => $tutor->id,
                'starts_at' => $day->copy()->setTime($hour, 0),
                'ends_at' => $day->copy()->setTime($hour + 1, 0),
                'status' => 'open',
            ]);
        }
    }
}
