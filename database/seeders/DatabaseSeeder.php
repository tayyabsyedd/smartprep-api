<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RosterSeeder::class,
            DemoUsersSeeder::class,
            ContentSeeder::class,
            TutorSeeder::class,
            DocumentSeeder::class,
        ]);
    }
}
