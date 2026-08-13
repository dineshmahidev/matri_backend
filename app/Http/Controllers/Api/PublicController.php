<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SuccessStory;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Services\GpayConfigService;
use App\Services\RazorpayConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicController extends Controller
{
    public function plans() { return response()->json(Plan::all()); }
    public function successStories() { return response()->json(SuccessStory::all()); }
    public function blog() { return response()->json(BlogPost::orderByDesc('published_at')->get()); }
    public function showBlog($slug) { 
        $post = BlogPost::where('slug', $slug)->firstOrFail();
        return response()->json($post);
    }
    public function showPage($slug) {
        $page = \App\Models\Page::where('slug', $slug)->first();
        if (!$page) return response()->json(['error' => 'Not found'], 404);
        return response()->json($page);
    }
    public function faqs() { return response()->json(Faq::orderBy('sort_order')->get()); }
    public function getSettings() {
        $data = Cache::remember('site_settings', 3600, fn() =>
            SiteSetting::all()->pluck('value', 'key')
        );
        return response()->json($data);
    }

    public function paymentGateways(RazorpayConfigService $razorpay, GpayConfigService $gpay)
    {
        return response()->json([
            'razorpay' => [
                'configured' => $razorpay->isConfigured(),
                'name' => 'Razorpay',
            ],
            'gpay' => $gpay->publicConfig(),
        ]);
    }

    public function contact(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        ContactMessage::create($data);
        return response()->json(['message' => 'Message sent successfully'], 201);
    }
}
