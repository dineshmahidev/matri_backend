<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How do I register on the platform?',
                'answer' => 'Click Register, fill in basic details, verify via OTP, and complete your profile in 3 simple steps.',
                'sort_order' => 1
            ],
            [
                'question' => 'Is my information kept private?',
                'answer' => 'Absolutely. We use bank-grade encryption and you control who can view your contact information.',
                'sort_order' => 2
            ],
            [
                'question' => 'What is the difference between membership plans?',
                'answer' => 'Silver gives essentials, Gold unlocks contacts and advanced filters, Platinum adds a relationship manager and verified badge.',
                'sort_order' => 3
            ],
            [
                'question' => 'Can I cancel my subscription anytime?',
                'answer' => 'Yes. You can manage and cancel your subscription from your dashboard at any time.',
                'sort_order' => 4
            ],
            [
                'question' => 'How are profiles verified?',
                'answer' => 'We verify via mobile OTP, ID proof and optional video verification for premium members.',
                'sort_order' => 5
            ],
            [
                'question' => 'Do you offer refunds?',
                'answer' => 'Refunds are available within 7 days of purchase if no premium features have been used.',
                'sort_order' => 6
            ]
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
