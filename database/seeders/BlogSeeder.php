<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BlogPost;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Clear old blogs first so we only have the updated realistic ones
        BlogPost::truncate();

        $posts = [
            [
                'title' => '10 Conversation Starters for Your First Match',
                'category' => 'Dating Tips',
                'read_time' => '5 min read',
                'published_at' => '2026-06-01',
                'image' => 'https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?w=900&auto=format&fit=crop',
                'excerpt' => 'Break the ice with thoughtful, genuine questions that lead to real connection.',
                'body' => '<h3>Starting the Conversation</h3><p>Finding the right words for your first conversation can feel intimidating, especially when looking for a life partner. You want to show genuine interest without sounding like you are conducting an interview.</p><h4>1. Discuss Hobbies Passionately</h4><p>Instead of "What are your hobbies?", ask "What is something you absolutely love doing on a Sunday afternoon?" This invites a story rather than a one-word answer.</p><h4>2. Career Ambitions</h4><p>Ask "What excites you most about your work?" to gauge their professional drive and passion.</p><h4>3. Family Values</h4><p>A great way to discuss family is asking, "What is your favorite family tradition?" This is culturally significant and opens the door to understanding their background.</p><h4>4. Travel Dreams</h4><p>"If you could instantly travel anywhere right now, where would you go and why?" Travel preferences often reveal a lot about a person\'s lifestyle and adaptability.</p><p>Remember, the goal is to listen actively and let the conversation flow naturally. Authenticity is the best conversation starter!</p>'
            ],
            [
                'title' => 'How to Spot a Compatible Partner',
                'category' => 'Relationships',
                'read_time' => '7 min read',
                'published_at' => '2026-05-24',
                'image' => 'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?w=900&auto=format&fit=crop',
                'excerpt' => 'Beyond the checklist — values, communication and emotional maturity matter most.',
                'body' => '<h3>Beyond the Bio</h3><p>While a well-written profile and matching horoscopes (porutham) are great starting points, true compatibility goes much deeper. How do you know if the person you are speaking with is truly right for you?</p><h4>Shared Core Values</h4><p>Do you agree on fundamental life goals? Whether it is career ambitions, financial habits, or how you wish to raise a family, having aligned core values is crucial for long-term harmony.</p><h4>Communication Style</h4><p>Pay attention to how they handle disagreements. Do they listen to understand, or do they just wait for their turn to speak? A compatible partner respects your opinions even when they differ from their own.</p><h4>Emotional Maturity</h4><p>A mature partner takes responsibility for their actions and can navigate life\'s ups and downs with resilience. Look for signs of empathy and support in your everyday conversations.</p><p>Ultimately, compatibility is about finding someone who makes you feel safe, respected, and completely yourself.</p>'
            ],
            [
                'title' => 'Planning Your First Meet — A Safe Guide',
                'category' => 'Safety',
                'read_time' => '4 min read',
                'published_at' => '2026-05-12',
                'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=900&auto=format&fit=crop',
                'excerpt' => 'Public places, shared plans, trusted friends — make the first meeting comfortable.',
                'body' => '<h3>Your Safety is the Priority</h3><p>Meeting someone in person for the first time is exciting, but it\'s essential to prioritize your safety and comfort. Here is a definitive guide to making your first meet a success.</p><h4>Choose a Public Place</h4><p>Always meet in a well-lit, public location like a popular café, a busy restaurant, or a mall. Avoid secluded areas or private residences for the first few meetings.</p><h4>Inform a Trusted Friend or Family Member</h4><p>Before you head out, share your location, the details of the person you are meeting, and the time you expect to return with a close friend or sibling. Consider sharing your live location on WhatsApp.</p><h4>Arrange Your Own Transportation</h4><p>Drive yourself or take a reliable cab service. Having control over your transportation ensures you can leave whenever you feel like it without relying on your date.</p><h4>Trust Your Instincts</h4><p>If something feels off during the meeting, do not hesitate to end the date early. Your intuition is your best guide. Stay safe and enjoy the journey!</p>'
            ],
            [
                'title' => 'Wedding Trends 2026',
                'category' => 'Inspiration',
                'read_time' => '6 min read',
                'published_at' => '2026-04-30',
                'image' => 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=900&auto=format&fit=crop',
                'excerpt' => 'From intimate destination weddings to sustainable celebrations — what\'s new.',
                'body' => '<h3>The Future of Celebrations</h3><p>The matrimonial landscape is evolving, and so are wedding celebrations. 2026 is seeing a beautiful blend of traditional customs and modern sensibilities.</p><h4>Eco-Friendly & Sustainable Weddings</h4><p>Couples are increasingly opting for zero-waste weddings. This includes digital invitations, locally sourced organic food, and decor made from biodegradable materials or reusable items.</p><h4>Intimate Destination Weddings</h4><p>The massive 1000-guest functions are making way for intimate gatherings of 100-150 close family and friends at picturesque heritage properties, beach resorts, or hill stations. It allows couples to spend quality time with their favorite people.</p><h4>Personalized Cultural Fusion</h4><p>With more inter-cultural matches, weddings are beautifully blending traditions. Think a traditional South Indian Muhurtham in the morning followed by a vibrant North Indian style Sangeet in the evening.</p><p>No matter the trend, the best weddings are those that authentically reflect the couple\'s personality and love story!</p>'
            ]
        ];

        foreach ($posts as $post) {
            $post['slug'] = Str::slug($post['title']);
            BlogPost::create($post);
        }
    }
}

