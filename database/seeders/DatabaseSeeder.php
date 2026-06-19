<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SkalyanaReferenceSeeder::class,
            PlanSeeder::class,
            SuccessStorySeeder::class,
            BlogSeeder::class,
            FaqSeeder::class,
            UserSeeder::class,
            LeadSeeder::class,
            ChatSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
