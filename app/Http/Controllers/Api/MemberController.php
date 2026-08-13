<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Interest;
use App\Models\User;
use App\Support\MemberVisibility;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function browse(Request $request)
    {
        $query = User::where('role', 'member')
            ->whereHas('profile')
            ->with('profile.gallery', 'profile.familyDetail', 'profile.partnerPreference', 'activeSubscription.plan');

        $user = auth('sanctum')->user();

        // Exclude the logged-in user from browse results
        if ($user) {
            $query->where('id', '!=', $user->id);
        }

        // Filters
        if ($request->religion) {
            $query->whereHas('profile', fn($q) => $q->where('religion', $request->religion));
        }
        if ($request->city) {
            $query->whereHas('profile', fn($q) => $q->where('city', $request->city));
        }
        if ($request->education) {
            $query->whereHas('profile', fn($q) => $q->where('education', $request->education));
        }
        if ($request->mother_tongue) {
            $query->whereHas('profile', fn($q) => $q->where('mother_tongue', $request->mother_tongue));
        }
        if ($request->community || $request->caste) {
            $val = $request->community ?? $request->caste;
            $query->whereHas('profile', fn($q) => $q->where('community', $val));
        }
        if ($request->age_min && $request->age_max) {
            $query->whereHas('profile', fn($q) => $q->whereBetween('age', [$request->age_min, $request->age_max]));
        }

        MemberVisibility::applyGenderScope($query, $user, $request->gender);
        MemberVisibility::applyBlockScope($query, $user);

        if ($request->premium) {
            $query->whereHas('profile', fn($q) => $q->premium());
        }

        MemberVisibility::applyPhotoPriority($query);

        $members = $query->latest()->paginate($request->per_page ?? 24);

        if ($user) {
            $memberIds = $members->pluck('id');
            $user->load(['savedProfiles' => fn($q) => $q->whereIn('saved_user_id', $memberIds),
                         'unlockedProfiles' => fn($q) => $q->whereIn('unlocked_user_id', $memberIds)]);
            $interestSentIds = Interest::where('sender_id', $user->id)->whereIn('receiver_id', $memberIds)->pluck('receiver_id')->toArray();
            $savedIds = $user->savedProfiles->pluck('id')->toArray();
            $unlockedIds = $user->unlockedProfiles->pluck('id')->toArray();
            $members->each(function ($m) use ($savedIds, $interestSentIds, $unlockedIds) {
                $m->viewer_saved = in_array($m->id, $savedIds);
                $m->viewer_interest_sent = in_array($m->id, $interestSentIds);
                $m->viewer_unlocked = in_array($m->id, $unlockedIds);
            });
        }

        return MemberResource::collection($members);
    }

    public function recommended(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $oppositeGender = $user->gender === 'male' ? 'female' : ($user->gender === 'female' ? 'male' : null);
        $perPage = (int) ($request->per_page ?? 24);
        $minResults = min(10, $perPage);

        // Base query: always applied
        $baseQuery = User::where('role', 'member')
            ->where('id', '!=', $user->id)
            ->whereHas('profile')
            ->with('profile.gallery', 'profile.familyDetail', 'profile.partnerPreference', 'activeSubscription.plan');

        if ($oppositeGender) {
            $baseQuery->where('gender', $oppositeGender);
        }

        // Always exclude already-interacted profiles
        $baseQuery->whereNotIn('id', fn($q) => $q->select('receiver_id')->from('interests')->where('sender_id', $user->id))
                  ->whereNotIn('id', fn($q) => $q->select('saved_user_id')->from('saved_profiles')->where('user_id', $user->id))
                  ->whereNotIn('id', fn($q) => $q->select('unlocked_user_id')->from('unlocked_profiles')->where('user_id', $user->id));

        MemberVisibility::applyBlockScope($baseQuery, $user);

        $profile = $user->profile;
        $collectedIds = collect();
        $allResults = collect();

        if ($profile) {
            $preference = $profile->partnerPreference;

            // Determine own preference values
            $prefReligion = null;
            $prefCommunity = null;
            $ageMin = null;
            $ageMax = null;

            if ($preference) {
                if ($preference->religion && strtolower($preference->religion) !== 'open to all') {
                    $prefReligion = $preference->religion;
                }
                if ($preference->community && strtolower($preference->community) !== 'open to all') {
                    $prefCommunity = $preference->community;
                }
                if ($preference->age_range) {
                    $parts = explode('-', $preference->age_range);
                    if (count($parts) === 2) {
                        $ageMin = (int) trim($parts[0]);
                        $ageMax = (int) trim($parts[1]);
                    }
                }
            } else {
                if ($profile->religion) $prefReligion = $profile->religion;
                if ($profile->community) $prefCommunity = $profile->community;
            }

            // Level 1: Mutual matching + own preferences (strictest)
            $q1 = clone $baseQuery;
            $this->applyOwnPreferences($q1, $prefReligion, $prefCommunity, $ageMin, $ageMax);
            $this->applyMutualMatching($q1, $profile);
            $results = $q1->inRandomOrder()->take($perPage)->get();
            $results->each->setAttribute('recommendation_level', 1);
            $collectedIds = $results->pluck('id');
            $allResults = $results;

            // Level 2: Drop mutual, keep own preferences
            if ($allResults->count() < $minResults) {
                $q2 = (clone $baseQuery)->whereNotIn('id', $collectedIds);
                $this->applyOwnPreferences($q2, $prefReligion, $prefCommunity, $ageMin, $ageMax);
                $results = $q2->inRandomOrder()->take($perPage - $allResults->count())->get();
                $results->each->setAttribute('recommendation_level', 2);
                $collectedIds = $collectedIds->merge($results->pluck('id'));
                $allResults = $allResults->merge($results);
            }

            // Level 3: Drop community, keep religion + age
            if ($allResults->count() < $minResults && $prefReligion) {
                $q3 = (clone $baseQuery)->whereNotIn('id', $collectedIds);
                $this->applyReligionAge($q3, $prefReligion, $ageMin, $ageMax);
                $results = $q3->inRandomOrder()->take($perPage - $allResults->count())->get();
                $results->each->setAttribute('recommendation_level', 3);
                $collectedIds = $collectedIds->merge($results->pluck('id'));
                $allResults = $allResults->merge($results);
            }

            // Level 4: Drop religion, keep age only
            if ($allResults->count() < $minResults && $ageMin !== null) {
                $q4 = (clone $baseQuery)->whereNotIn('id', $collectedIds);
                $q4->whereHas('profile', fn($q) => $q->whereBetween('age', [$ageMin, $ageMax]));
                $results = $q4->inRandomOrder()->take($perPage - $allResults->count())->get();
                $results->each->setAttribute('recommendation_level', 4);
                $collectedIds = $collectedIds->merge($results->pluck('id'));
                $allResults = $allResults->merge($results);
            }
        }

        // Level 5: No preference filters (just opposite gender + exclude interacted)
        if ($allResults->count() < $minResults) {
            $q5 = (clone $baseQuery)->whereNotIn('id', $collectedIds);
            $results = $q5->inRandomOrder()->take($perPage - $allResults->count())->get();
            $results->each->setAttribute('recommendation_level', 5);
            $allResults = $allResults->merge($results);
        }

        // Attach "Why recommended" reason per member
        $allResults->each(function ($m) use ($profile) {
            $m->recommendation_reason = $this->recommendationReason($m, $profile, (int) $m->recommendation_level);
        });

        // Sort: profiles with photos first, then premium members, then the rest
        $allResults = $allResults->sortByDesc(function ($m) {
            $hasPhoto = $m->profile?->photo ? 1 : 0;
            $isPremium = $m->profile?->premium ? 1 : 0;
            return $hasPhoto * 10 + $isPremium;
        })->values();

        // Manual pagination
        $total = $allResults->count();
        $page = (int) ($request->page ?? 1);
        $offset = ($page - 1) * $perPage;
        $slice = $allResults->slice($offset, $perPage)->values();
        $members = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $memberIds = $members->pluck('id');
        $user->load(['savedProfiles' => fn($q) => $q->whereIn('saved_user_id', $memberIds),
                     'unlockedProfiles' => fn($q) => $q->whereIn('unlocked_user_id', $memberIds)]);
        $interestSentIds = Interest::where('sender_id', $user->id)->whereIn('receiver_id', $memberIds)->pluck('receiver_id')->toArray();
        $savedIds = $user->savedProfiles->pluck('id')->toArray();
        $unlockedIds = $user->unlockedProfiles->pluck('id')->toArray();
        $members->each(function ($m) use ($savedIds, $interestSentIds, $unlockedIds) {
            $m->viewer_saved = in_array($m->id, $savedIds);
            $m->viewer_interest_sent = in_array($m->id, $interestSentIds);
            $m->viewer_unlocked = in_array($m->id, $unlockedIds);
        });

        return MemberResource::collection($members);
    }

    private function applyOwnPreferences($query, $religion, $community, $ageMin, $ageMax): void
    {
        if ($religion) {
            $query->whereHas('profile', fn($q) => $q->where('religion', $religion));
        }
        if ($community) {
            $query->whereHas('profile', fn($q) => $q->where('community', $community));
        }
        if ($ageMin !== null) {
            $query->whereHas('profile', fn($q) => $q->whereBetween('age', [$ageMin, $ageMax]));
        }
    }

    private function recommendationReason($member, $profile, int $level): string
    {
        $p = $member->profile;
        if (!$p || !$profile) {
            return $this->levelLabel($level);
        }

        $preference = $profile->partnerPreference;
        $prefReligion = null;
        $prefCommunity = null;
        $prefAge = null;

        if ($preference) {
            if ($preference->religion && strtolower($preference->religion) !== 'open to all') {
                $prefReligion = $preference->religion;
            }
            if ($preference->community && strtolower($preference->community) !== 'open to all') {
                $prefCommunity = $preference->community;
            }
            if ($preference->age_range) {
                $prefAge = $preference->age_range;
            }
        } else {
            if ($profile->religion) $prefReligion = $profile->religion;
            if ($profile->community) $prefCommunity = $profile->community;
        }

        $reasons = [];

        if ($prefReligion && $p->religion === $prefReligion) {
            $reasons[] = 'Matches your religion (' . $prefReligion . ')';
        }
        if ($prefCommunity && $p->community === $prefCommunity) {
            $reasons[] = 'Matches your community (' . $prefCommunity . ')';
        }
        if ($prefAge && $p->age !== null) {
            $parts = explode('-', $prefAge);
            if (count($parts) === 2) {
                $min = (int) trim($parts[0]);
                $max = (int) trim($parts[1]);
                if ($p->age >= $min && $p->age <= $max) {
                    $reasons[] = 'Age ' . $p->age . ' within your preference (' . $min . '-' . $max . ')';
                }
            }
        }
        if ($p->mother_tongue && $p->mother_tongue === $profile->mother_tongue) {
            $reasons[] = 'Same mother tongue (' . $p->mother_tongue . ')';
        }
        if ($p->city && $p->city === $profile->city) {
            $reasons[] = 'Located in ' . $p->city;
        }

        if (count($reasons) > 0) {
            return implode(', ', array_slice($reasons, 0, 2)) . '.';
        }

        return $this->levelLabel($level);
    }

    private function levelLabel(int $level): string
    {
        return match ($level) {
            1 => 'Mutual match based on your preferences.',
            2 => 'Matches your partner preferences.',
            3 => 'Shares your religion and preferred age.',
            4 => 'Within your preferred age range.',
            default => 'Suggested based on your profile.',
        };
    }

    private function applyReligionAge($query, $religion, $ageMin, $ageMax): void
    {
        $query->whereHas('profile', fn($q) => $q->where('religion', $religion));
        if ($ageMin !== null) {
            $query->whereHas('profile', fn($q) => $q->whereBetween('age', [$ageMin, $ageMax]));
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

    public function show(string $id)
    {
        $viewer = auth('sanctum')->user();

        $user = User::where('role', 'member')
            ->where(function ($q) use ($id) {
                $q->whereHas('profile', fn($p) => $p->where('display_id', $id));
                if (is_numeric($id)) {
                    $q->orWhere('id', (int)$id);
                }
                // Handle UK00 format: UK00{10000+user_id}
                if (preg_match('/^UK00(\d{4,})$/', $id, $m)) {
                    $userIdFromDisplay = (int)$m[1] - 10000;
                    if ($userIdFromDisplay > 0) {
                        $q->orWhere('id', $userIdFromDisplay);
                    }
                }
            })
            ->with('profile.gallery', 'profile.familyDetail', 'profile.partnerPreference', 'activeSubscription.plan')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $opposite = MemberVisibility::oppositeGender($viewer);
        if ($opposite && $user->gender !== $opposite && (!$viewer || $user->id !== $viewer->id)) {
            abort(404);
        }

        return new MemberResource($user);
    }

    public function recentlyJoined(Request $request)
    {
        $viewer = auth('sanctum')->user();

        $query = User::where('role', 'member')
            ->whereHas('profile')
            ->with('profile.gallery', 'activeSubscription.plan');

        if ($viewer) {
            $query->where('users.id', '!=', $viewer->id);
        }

        MemberVisibility::applyGenderScope($query, $viewer);
        MemberVisibility::applyBlockScope($query, $viewer);

        // Priority sorting: premium members first, then profiles with photos, then latest created
        $query->join('member_profiles', 'users.id', '=', 'member_profiles.user_id')
            ->select('users.*')
            ->orderByRaw('CASE WHEN member_profiles.premium = 1 THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN member_profiles.photo IS NOT NULL AND member_profiles.photo != "" THEN 0 ELSE 1 END')
            ->orderByDesc('users.created_at');

        $members = $query->paginate($request->per_page ?? 30);

        if ($viewer) {
            $memberIds = $members->pluck('id');
            $viewer->load(['savedProfiles' => fn($q) => $q->whereIn('saved_user_id', $memberIds),
                           'unlockedProfiles' => fn($q) => $q->whereIn('unlocked_user_id', $memberIds)]);
            $interestSentIds = Interest::where('sender_id', $viewer->id)->whereIn('receiver_id', $memberIds)->pluck('receiver_id')->toArray();
            $savedIds = $viewer->savedProfiles->pluck('id')->toArray();
            $unlockedIds = $viewer->unlockedProfiles->pluck('id')->toArray();
            $members->each(function ($m) use ($savedIds, $interestSentIds, $unlockedIds) {
                $m->viewer_saved = in_array($m->id, $savedIds);
                $m->viewer_interest_sent = in_array($m->id, $interestSentIds);
                $m->viewer_unlocked = in_array($m->id, $unlockedIds);
            });
        }

        return MemberResource::collection($members);
    }

    public function featuredMembers(Request $request)
    {
        $viewer = auth('sanctum')->user();

        $query = User::where('role', 'member')
            ->whereHas('profile', fn($q) => $q->featured())
            ->with('profile.gallery', 'activeSubscription.plan');

        if ($request->boolean('has_photo')) {
            $query->whereHas('profile', fn($q) => $q->whereNotNull('photo')->where('photo', '!=', ''));
        }

        if ($viewer) {
            $query->where('id', '!=', $viewer->id);
        }

        MemberVisibility::applyGenderScope($query, $viewer);
        MemberVisibility::applyBlockScope($query, $viewer);

        MemberVisibility::applyPhotoPriority($query);

        $members = $query->inRandomOrder()->take(20)->get();

        if ($viewer) {
            $memberIds = $members->pluck('id');
            $viewer->load(['savedProfiles' => fn($q) => $q->whereIn('saved_user_id', $memberIds),
                           'unlockedProfiles' => fn($q) => $q->whereIn('unlocked_user_id', $memberIds)]);
            $interestSentIds = Interest::where('sender_id', $viewer->id)->whereIn('receiver_id', $memberIds)->pluck('receiver_id')->toArray();
            $savedIds = $viewer->savedProfiles->pluck('id')->toArray();
            $unlockedIds = $viewer->unlockedProfiles->pluck('id')->toArray();
            $members->each(function ($m) use ($savedIds, $interestSentIds, $unlockedIds) {
                $m->viewer_saved = in_array($m->id, $savedIds);
                $m->viewer_interest_sent = in_array($m->id, $interestSentIds);
                $m->viewer_unlocked = in_array($m->id, $unlockedIds);
            });
        }

        return MemberResource::collection($members);
    }

    public function premiumMembers(Request $request)
    {
        $viewer = auth('sanctum')->user();

        $query = User::where('role', 'member')
            ->whereHas('profile', fn($q) => $q->premium())
            ->with('profile.gallery', 'activeSubscription.plan');

        if ($viewer) {
            $query->where('id', '!=', $viewer->id);
        }

        MemberVisibility::applyGenderScope($query, $viewer);
        MemberVisibility::applyBlockScope($query, $viewer);

        MemberVisibility::applyPhotoPriority($query);

        $members = $query->latest()->paginate($request->per_page ?? 24);

        if ($viewer) {
            $memberIds = $members->pluck('id');
            $viewer->load(['savedProfiles' => fn($q) => $q->whereIn('saved_user_id', $memberIds),
                           'unlockedProfiles' => fn($q) => $q->whereIn('unlocked_user_id', $memberIds)]);
            $interestSentIds = Interest::where('sender_id', $viewer->id)->whereIn('receiver_id', $memberIds)->pluck('receiver_id')->toArray();
            $savedIds = $viewer->savedProfiles->pluck('id')->toArray();
            $unlockedIds = $viewer->unlockedProfiles->pluck('id')->toArray();
            $members->each(function ($m) use ($savedIds, $interestSentIds, $unlockedIds) {
                $m->viewer_saved = in_array($m->id, $savedIds);
                $m->viewer_interest_sent = in_array($m->id, $interestSentIds);
                $m->viewer_unlocked = in_array($m->id, $unlockedIds);
            });
        }

        return MemberResource::collection($members);
    }

    public function unlock(Request $request, string $id)
    {
        $user = $request->user();
        $oppositeUser = User::where('role', 'member')
            ->where(function ($q) use ($id) {
                $q->whereHas('profile', fn($q) => $q->where('display_id', $id));
                if (is_numeric($id)) {
                    $q->orWhere('id', (int)$id);
                }
                if (preg_match('/^UK00(\d{4,})$/', $id, $m)) {
                    $userIdFromDisplay = (int)$m[1] - 10000;
                    if ($userIdFromDisplay > 0) {
                        $q->orWhere('id', $userIdFromDisplay);
                    }
                }
            })
            ->first();

        if (!$oppositeUser) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $alreadyUnlocked = $user->unlockedProfiles()
            ->where('unlocked_user_id', $oppositeUser->id)
            ->exists();

        if ($alreadyUnlocked) {
            return response()->json([
                'message' => 'Profile is already unlocked.',
                'contact_quota' => $user->contact_quota,
                'phone' => $oppositeUser->phone,
                'email' => $oppositeUser->email,
            ]);
        }

        $settings = \App\Models\SiteSetting::pluck('value', 'key');
        if ($user->contact_quota < 1) {
            return response()->json([
                'message' => 'Insufficient contact view credits. Please top up your account to view contact details.',
                'is_premium' => $user->isPremium(),
            ], 400);
        }
        $user->decrement('contact_quota');

        $user->unlockedProfiles()->attach($oppositeUser->id);

        return response()->json([
            'message' => 'Profile unlocked successfully! 1 contact view credit used.',
            'contact_quota' => $user->contact_quota,
            'phone' => $oppositeUser->phone,
            'email' => $oppositeUser->email,
        ]);
    }
}
