<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $me = User::whereHas('profile', fn($p) => $p->where('display_id', 'UK0010009'))->first();
        if (!$me) {
            $me = User::where('role', 'member')->first();
        }

        if (!$me) return;

        $notifications = [
            [
                'title' => 'New interest received',
                'description' => 'Priya I. is interested in your profile',
                'type' => 'interest',
                'read' => false,
            ],
            [
                'title' => 'Profile match',
                'description' => '5 new matches based on your preferences',
                'type' => 'match',
                'read' => false,
            ],
            [
                'title' => 'Message',
                'description' => 'Aarav V. sent you a message',
                'type' => 'message',
                'read' => true,
            ],
            [
                'title' => 'Profile viewed',
                'description' => 'Your profile was viewed 12 times today',
                'type' => 'view',
                'read' => true,
            ]
        ];

        foreach ($notifications as $notification) {
            $notification['user_id'] = $me->id;
            if (Notification::where('user_id', $me->id)->where('title', $notification['title'])->exists()) continue;
            Notification::create($notification);
        }
    }
}
