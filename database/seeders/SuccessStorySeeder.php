<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SuccessStory;

class SuccessStorySeeder extends Seeder
{
    public function run(): void
    {
        $stories = [
            [
                'couple_name' => 'Anjali & Rohan',
                'date' => 'March 2025',
                'city' => 'Mumbai',
                'photo' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=900&auto=format&fit=crop',
                'quote' => 'We matched on day one and knew it was meant to be. Forever grateful to this platform.'
            ],
            [
                'couple_name' => 'Sneha & Aditya',
                'date' => 'January 2025',
                'city' => 'Bengaluru',
                'photo' => 'https://images.unsplash.com/photo-1606216794074-735e91aa2c92?w=900&auto=format&fit=crop',
                'quote' => 'Verified profiles and genuine connections — that\'s what made the difference for us.'
            ],
            [
                'couple_name' => 'Priya & Karthik',
                'date' => 'November 2024',
                'city' => 'Chennai',
                'photo' => 'https://images.unsplash.com/photo-1583939003579-730e3918a45a?w=900&auto=format&fit=crop',
                'quote' => 'From first message to wedding bells in 8 months. Thank you for bringing us together.'
            ]
        ];

        foreach ($stories as $story) {
            if (SuccessStory::where('couple_name', $story['couple_name'])->exists()) continue;
            SuccessStory::create($story);
        }
    }
}
