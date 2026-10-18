<?php

namespace Database\Seeders;

use App\Models\EnrollmentRequest;
use App\Models\InstituteRoster;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $enrolled = User::firstOrCreate(
            ['email' => 'enrolled@smartprep.local'],
            [
                'name' => 'Demo Enrolled Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '03001112223',
            ]
        );

        // Claim ROLL-100 the same way the verify endpoint does.
        $roster = InstituteRoster::where('enrollment_id', 'ROLL-100')->first();
        if ($roster && ! $roster->is_claimed) {
            $roster->update(['is_claimed' => true, 'claimed_by' => $enrolled->id]);
        }
        $enrolled->update(['is_enrolled' => true, 'enrollment_id' => 'ROLL-100']);

        EnrollmentRequest::firstOrCreate(
            ['user_id' => $enrolled->id, 'enrollment_id' => 'ROLL-100'],
            ['status' => 'approved', 'reviewed_at' => now(), 'note' => 'Seeded demo enrollment.']
        );

        User::firstOrCreate(
            ['email' => 'guest@smartprep.local'],
            [
                'name' => 'Demo Guest Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '03003334445',
            ]
        );
    }
}
