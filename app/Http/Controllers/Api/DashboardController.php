<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use Illuminate\Http\Request;
use App\Services\ProfileCompletionService;

class DashboardController extends Controller
{
    public function __construct(private ProfileCompletionService $profileCompletion) {}

    public function index(Request $request)
    {
        $user = $request->user()->load('profile', 'activeSubscription.plan');

        $sentCount = $user->sentInterests()->count();
        $receivedCount = $user->receivedInterests()->count();
        $unreadMessages = $user->notifications()->where('type', 'message')->where('read', false)->count();
        $profileViews = rand(800, 1500); // In production, track actual views

        $oppositeGender = $user->gender === 'male' ? 'female' : ($user->gender === 'female' ? 'male' : null);

        $query = \App\Models\User::where('role', 'member')
            ->where('id', '!=', $user->id)
            ->whereHas('profile')
            ->with('profile.gallery', 'activeSubscription.plan');

        if ($oppositeGender) {
            $query->where('gender', $oppositeGender);
        }

        $profile = $user->profile;
        if ($profile) {
            $preference = $profile->partnerPreference;
            if ($preference) {
                if ($preference->religion && strtolower($preference->religion) !== 'open to all') {
                    $query->whereHas('profile', fn($q) => $q->where('religion', $preference->religion));
                }
                if ($preference->community && strtolower($preference->community) !== 'open to all') {
                    $query->whereHas('profile', fn($q) => $q->where('community', $preference->community));
                }
            } else {
                if ($profile->religion) {
                    $query->whereHas('profile', fn($q) => $q->where('religion', $profile->religion));
                }
            }
        }

        $matches = $query->inRandomOrder()->take(4)->get();

        // If not enough compatible matches, merge fallback matches of the opposite gender
        if ($matches->count() < 4) {
            $fallbackQuery = \App\Models\User::where('role', 'member')
                ->where('id', '!=', $user->id)
                ->whereHas('profile')
                ->with('profile.gallery', 'activeSubscription.plan');
            if ($oppositeGender) {
                $fallbackQuery->where('gender', $oppositeGender);
            }
            $fallbackIds = $matches->pluck('id');
            $fallbackMatches = $fallbackQuery->whereNotIn('id', $fallbackIds)->inRandomOrder()->take(4 - $matches->count())->get();
            $matches = $matches->merge($fallbackMatches);
        }

        $notifications = $user->notifications()->latest()->take(5)->get();
        $payments = $user->payments()->latest()->take(3)->get();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'photo' => $user->profile?->photo,
                'gallery' => $user->profile?->gallery?->pluck('image_url') ?? [],
                'is_premium' => $user->activeSubscription?->plan?->slug !== null,
                'profile_completion' => $this->profileCompletion->completionPercent($user),
                'profile_complete' => $this->profileCompletion->isEssentiallyComplete($user),
                'missing_fields' => $this->profileCompletion->missingFields($user),
                'membership' => $user->activeSubscription?->plan?->name ?? 'Free',
                'membership_valid_until' => $user->activeSubscription?->ends_at?->format('M d, Y'),
                'credits' => $user->credits,
                'contact_quota' => $user->contact_quota,
                'message_quota' => $user->message_quota,
            ],
            'stats' => [
                'profile_views' => number_format($profileViews),
                'profile_views_change' => '+12%',
                'interests_received' => $receivedCount,
                'interests_sent' => $sentCount,
                'messages' => $unreadMessages . ' unread',
            ],
            'matches' => MemberResource::collection($matches),
            'notifications' => $notifications,
            'payments' => $payments,
        ]);
    }
}
