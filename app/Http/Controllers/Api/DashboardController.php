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

        $baseQuery = \App\Models\User::where('role', 'member')
            ->where('id', '!=', $user->id)
            ->whereHas('profile')
            ->with('profile.gallery', 'activeSubscription.plan');

        if ($oppositeGender) {
            $baseQuery->where('gender', $oppositeGender);
        }

        // Exclude already-interacted profiles
        $baseQuery->whereNotIn('id', fn($q) => $q->select('receiver_id')->from('interests')->where('sender_id', $user->id))
                  ->whereNotIn('id', fn($q) => $q->select('saved_user_id')->from('saved_profiles')->where('user_id', $user->id))
                  ->whereNotIn('id', fn($q) => $q->select('unlocked_user_id')->from('unlocked_profiles')->where('user_id', $user->id));

        $profile = $user->profile;
        $collectedIds = collect();
        $matches = collect();

        if ($profile) {
            $preference = $profile->partnerPreference;

            $prefReligion = null;
            $prefCommunity = null;

            if ($preference) {
                if ($preference->religion && strtolower($preference->religion) !== 'open to all') {
                    $prefReligion = $preference->religion;
                }
                if ($preference->community && strtolower($preference->community) !== 'open to all') {
                    $prefCommunity = $preference->community;
                }
            } else {
                if ($profile->religion) $prefReligion = $profile->religion;
                if ($profile->community) $prefCommunity = $profile->community;
            }

            // Level 1: Mutual + own preferences
            $q1 = clone $baseQuery;
            $this->applyOwnPreferences($q1, $prefReligion, $prefCommunity);
            $this->applyMutualMatching($q1, $profile);
            $results = $q1->inRandomOrder()->take(4)->get();
            $collectedIds = $results->pluck('id');
            $matches = $results;

            // Level 2: Drop mutual, keep own prefs
            if ($matches->count() < 4) {
                $q2 = (clone $baseQuery)->whereNotIn('id', $collectedIds);
                $this->applyOwnPreferences($q2, $prefReligion, $prefCommunity);
                $results = $q2->inRandomOrder()->take(4 - $matches->count())->get();
                $collectedIds = $collectedIds->merge($results->pluck('id'));
                $matches = $matches->merge($results);
            }

            // Level 3: Drop community, keep religion only
            if ($matches->count() < 4 && $prefReligion) {
                $q3 = (clone $baseQuery)->whereNotIn('id', $collectedIds);
                $q3->whereHas('profile', fn($q) => $q->where('religion', $prefReligion));
                $results = $q3->inRandomOrder()->take(4 - $matches->count())->get();
                $collectedIds = $collectedIds->merge($results->pluck('id'));
                $matches = $matches->merge($results);
            }
        }

        // Level 4: No preference filters
        if ($matches->count() < 4) {
            $q4 = (clone $baseQuery)->whereNotIn('id', $collectedIds);
            $results = $q4->inRandomOrder()->take(4 - $matches->count())->get();
            $matches = $matches->merge($results);
        }

        // Sort: profiles with photos first, then premium members, then the rest
        $matches = $matches->sortByDesc(function ($m) {
            $hasPhoto = $m->profile?->photo ? 1 : 0;
            $isPremium = $m->profile?->premium ? 1 : 0;
            return $hasPhoto * 10 + $isPremium;
        })->values();

        $notifications = $user->notifications()->latest()->take(5)->get();
        $payments = $user->payments()->latest()->take(3)->get();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'photo' => $user->profile?->photo,
                'gender' => $user->gender ?? $user->profile?->gender,
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

    private function applyOwnPreferences($query, $religion, $community): void
    {
        if ($religion) {
            $query->whereHas('profile', fn($q) => $q->where('religion', $religion));
        }
        if ($community) {
            $query->whereHas('profile', fn($q) => $q->where('community', $community));
        }
    }

    private function applyMutualMatching($query, $profile): void
    {
        $query->where(function ($q) use ($profile) {
            $q->whereDoesntHave('profile.partnerPreference')
              ->orWhereHas('profile.partnerPreference', function ($pq) use ($profile) {
                  $pq->where(function ($rq) use ($profile) {
                      $rq->whereNull('religion')
                         ->orWhere('religion', '')
                         ->orWhere('religion', 'Open to all')
                         ->orWhere('religion', $profile->religion);
                  });
                  $pq->where(function ($cq) use ($profile) {
                      $cq->whereNull('community')
                         ->orWhere('community', '')
                         ->orWhere('community', 'Open to all')
                         ->orWhere('community', $profile->community);
                  });
              });
        });
    }
}
