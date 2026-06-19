<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $commonFeatures = [
            "Contact profile views",
            "Send interest to profiles",
            "Chat with matches",
            "AI Porutham matching",
            "Gallery access",
            "Profile highlights",
        ];

        $plans = [
            [
                'slug' => 'silver',
                'name' => 'Silver',
                'price' => 999,
                'period' => '3 months',
                'color' => 'from-slate-400 to-slate-600',
                'popular' => false,
                'contact_quota' => 30,
                'message_quota' => 50,
                'credits' => 100,
                'features' => $commonFeatures,
            ],
            [
                'slug' => 'gold',
                'name' => 'Gold',
                'price' => 2499,
                'period' => '6 months',
                'color' => 'from-amber-400 to-amber-600',
                'popular' => true,
                'contact_quota' => 100,
                'message_quota' => 200,
                'credits' => 500,
                'features' => $commonFeatures,
            ],
            [
                'slug' => 'platinum',
                'name' => 'Platinum',
                'price' => 4999,
                'period' => '12 months',
                'color' => 'from-rose-400 to-rose-600',
                'popular' => false,
                'contact_quota' => 500,
                'message_quota' => 1000,
                'credits' => 2000,
                'features' => $commonFeatures,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
