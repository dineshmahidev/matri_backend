<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        // Find users for mock chats
        $me = User::whereHas('profile', fn($p) => $p->where('display_id', 'UK0010009'))->first();
        if (!$me) {
            $me = User::where('role', 'member')->first();
        }

        if (!$me) return;

        $chatPartners = [
            'UK0010008' => [
                'last_message' => 'That sounds wonderful! Looking forward to it.',
                'unread' => 2,
                'messages' => [
                    ['from_partner' => true, 'text' => 'Hi! I came across your profile and really liked your bio.', 'time' => '10:30:00'],
                    ['from_partner' => false, 'text' => 'Thank you so much! That\'s very kind. I enjoyed reading yours too.', 'time' => '10:32:00'],
                    ['from_partner' => true, 'text' => 'Would you like to chat more? Maybe about your hobbies?', 'time' => '10:33:00'],
                    ['from_partner' => false, 'text' => 'Absolutely. I love reading, travel, and trying new cuisines!', 'time' => '10:35:00'],
                    ['from_partner' => true, 'text' => 'That sounds wonderful! Looking forward to it.', 'time' => '10:36:00'],
                ]
            ],
            'UK0010010' => [
                'last_message' => 'Thank you for sharing. We have so much in common!',
                'unread' => 0,
                'messages' => [
                    ['from_partner' => true, 'text' => 'Hello, nice to connect with you.', 'time' => '09:15:00'],
                    ['from_partner' => false, 'text' => 'Hi, likewise! I see you also live in Bengaluru.', 'time' => '09:20:00'],
                    ['from_partner' => true, 'text' => 'Yes, been here for 5 years now. Thank you for sharing. We have so much in common!', 'time' => '09:25:00'],
                ]
            ],
            'UK0010012' => [
                'last_message' => 'Would love to know more about your family.',
                'unread' => 1,
                'messages' => [
                    ['from_partner' => true, 'text' => 'Hi there, I read your profile. Would love to know more about your family.', 'time' => '08:00:00'],
                ]
            ],
            'UK0010014' => [
                'last_message' => 'Sure, let\'s plan a call this weekend.',
                'unread' => 0,
                'messages' => [
                    ['from_partner' => false, 'text' => 'Hello, can we connect on a call?', 'time' => 'Yesterday 14:00:00'],
                    ['from_partner' => true, 'text' => 'Sure, let\'s plan a call this weekend.', 'time' => 'Yesterday 15:30:00'],
                ]
            ]
        ];

        foreach ($chatPartners as $displayId => $data) {
            $partner = User::whereHas('profile', fn($p) => $p->where('display_id', $displayId))->first();
            if (!$partner) continue;

            $userA = $me->id < $partner->id ? $me : $partner;
            $userB = $me->id < $partner->id ? $partner : $me;

            // Skip if conversation already exists
            if (Conversation::where('user_a_id', $userA->id)->where('user_b_id', $userB->id)->exists()) continue;

            $unreadA = $userA->id === $me->id ? 0 : $data['unread'];
            $unreadB = $userB->id === $me->id ? 0 : $data['unread'];

            $conv = Conversation::create([
                'user_a_id' => $userA->id,
                'user_b_id' => $userB->id,
                'last_message' => $data['last_message'],
                'last_message_time' => '2m',
                'unread_count_a' => $unreadA,
                'unread_count_b' => $unreadB,
            ]);

            foreach ($data['messages'] as $msg) {
                $sender = $msg['from_partner'] ? $partner : $me;
                Message::create([
                    'conversation_id' => $conv->id,
                    'sender_id' => $sender->id,
                    'text' => $msg['text'],
                    'read_at' => $msg['from_partner'] && $data['unread'] > 0 ? null : Carbon::now(),
                ]);
            }
        }
    }
}
