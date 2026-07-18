<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'body' => '<p class="mb-6">At Ungalkalyanam, your privacy is our top priority. We are committed to protecting the personal information you share with us. This policy explains what data we collect, how we use it, and how we keep it safe.</p><h3>What Information Do We Collect?</h3><p class="mb-2">We collect information from you when you:</p><ul class="list-disc pl-6 mb-4 space-y-2"><li>Register on our website</li><li>Fill out a form or submit a request</li><li>Purchase any of our services</li><li>Respond to surveys or feedback</li></ul><p class="mb-6">You may be asked to provide your name, email address, phone number, or other relevant details.</p><h3>How Do We Protect Your Information?</h3><p class="mb-6">All sensitive data, including payment information, is securely processed via trusted gateways like Razorpay/Stripe. We do not store your credit/debit card details.</p><h3>Children\'s Privacy</h3><p class="mb-6">We comply with the Children\'s Online Privacy Protection Act. We do not knowingly collect information from anyone under the age of 18.</p><h3>Contact Us</h3><ul class="list-disc pl-6 mb-6"><li><strong>Email:</strong> ungalkalyanam.in@gmail.com</li><li><strong>Contact:</strong> 9597558432</li></ul>'
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'body' => '<p class="mb-6">Welcome to Ungalkalyanam. By accessing or using our website, you agree to comply with and be bound by the following terms and conditions of use.</p><h3>Acceptance of Terms</h3><p class="mb-6">Your access to and use of Ungalkalyanam is subject exclusively to these Terms and Conditions. You will not use the Website for any purpose that is unlawful or prohibited by these Terms.</p><h3>User Accounts</h3><p class="mb-6">To access certain features of the website, you must register for an account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p><h3>User Conduct</h3><p class="mb-6">You agree not to use the website in any way that could damage, disable, overburden, or impair the site or interfere with any other party\'s use and enjoyment of the website.</p><h3>Disclaimer</h3><p class="mb-6">The materials on Ungalkalyanam\'s website are provided on an \'as is\' basis. Ungalkalyanam makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p><h3>Contact Us</h3><ul class="list-disc pl-6 mb-6"><li><strong>Email:</strong> ungalkalyanam.in@gmail.com</li><li><strong>Contact:</strong> 9597558432</li></ul>'
            ]
        ];

        foreach ($pages as $page) {
            if (\App\Models\Page::where('slug', $page['slug'])->exists()) continue;
            \App\Models\Page::create($page);
        }

        $refundPage = [
            'slug' => 'refund-policy',
            'title' => 'Refund & Cancellation Policy',
            'body' => '<h2>No Refund Policy</h2><p>All payments made to Ungalkalyanam for membership plans, contact unlocks, and any other services are <strong>non-refundable</strong>.</p><h2>Cancellation</h2><p>Once a payment is successfully processed, the service is activated immediately. Therefore, cancellations and refund requests cannot be accommodated after the transaction is completed.</p><h2>Chargebacks</h2><p>Initiating a chargeback or payment dispute with your bank or payment provider (including Razorpay) will result in an immediate suspension of your account and all associated services. Your account may be permanently disabled.</p><h2>Contact Us</h2><ul class="list-disc pl-6 mb-6"><li><strong>Email:</strong> ungalkalyanam.in@gmail.com</li><li><strong>Contact:</strong> 9597558432</li></ul>'
        ];
        if (!\App\Models\Page::where('slug', $refundPage['slug'])->exists()) {
            \App\Models\Page::create($refundPage);
        }
    }
}
