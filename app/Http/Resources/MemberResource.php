<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->profile;
        $user = $request->user();
        
        if (!$user && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }
        
        $isSaved = false;
        $interestSent = false;
        $isUnlocked = false;
        if ($user) {
            $isSaved = $this->viewer_saved ?? $user->savedProfiles()->where('saved_user_id', $this->id)->exists();
            $interestSent = $this->viewer_interest_sent ?? \App\Models\Interest::where('sender_id', $user->id)
                ->where('receiver_id', $this->id)
                ->exists();
            $isUnlocked = ($user->id === $this->id) || ($this->viewer_unlocked ?? $user->unlockedProfiles()->where('unlocked_user_id', $this->id)->exists());
        }

        $isAdminOrStaff = $user && in_array($user->role, ['admin', 'staff']);
        $revealContact = $isUnlocked || $isAdminOrStaff;

        return [
            'id' => $profile?->display_id ?? 'UK00' . (10000 + $this->id),
            'userId' => $this->id,
            'role' => $this->when($user && $user->id === $this->id, $this->role),
            'isSaved' => $isSaved,
            'interestSent' => $interestSent,
            'isUnlocked' => $isUnlocked,
            'credits' => $this->credits,
            'message_quota' => (int) $this->message_quota,
            'contact_quota' => (int) $this->contact_quota,
            'planCredits' => $this->activeSubscription?->plan?->credits ?? 0,
            'planMessageQuota' => $this->activeSubscription?->plan?->message_quota ?? 0,
            'planContactQuota' => $this->activeSubscription?->plan?->contact_quota ?? 0,
            'phone' => $revealContact ? $this->phone : ($this->phone ? substr($this->phone, 0, 7) . '•••••' : null),
            'email' => $revealContact ? $this->email : ($this->email ? substr($this->email, 0, 3) . '•••••@example.com' : null),
            'name' => $this->name,
            'gender' => $this->gender,
            'dob' => $this->dob ? $this->dob->format('Y-m-d') : null,
            'tob' => $this->tob,
            'age' => $profile?->age,
            'height' => $profile?->height,
            'blood_group' => $profile?->blood_group,
            'religion' => $profile?->religion,
            'community' => $profile?->community,
            'motherTongue' => $profile?->mother_tongue,
            'city' => $profile?->city,
            'state' => $profile?->state,
            'country' => $profile?->country,
            'profession' => $profile?->profession,
            'education' => $profile?->education,
            'income' => $profile?->income,
            'maritalStatus' => $profile?->marital_status,
            'photo' => $profile?->photo,
            'gallery' => $profile?->gallery?->pluck('image_url') ?? [],
            'bio' => $profile?->bio,
            'rasi' => $profile?->rasi,
            'nakshatram' => $profile?->nakshatram,
            'premium' => (bool) $profile?->premium,
            'featured' => (bool) $profile?->featured,
            'planId' => $this->activeSubscription?->plan_id,
            'planName' => $this->activeSubscription?->plan?->name ?? 'Free Plan',
            'verified' => (bool) $profile?->verified,
            'online' => (bool) $profile?->online,
            'lastActive' => $profile?->online ? 'Online now' : ($profile?->last_active_at?->diffForHumans() ?? 'N/A'),
            'joinedDays' => $this->created_at?->diffInDays(now()) ?? 0,
            'family' => $this->when($profile?->familyDetail, function () use ($profile) {
                $f = $profile->familyDetail;
                return [
                    'father' => $f->father,
                    'mother' => $f->mother,
                    'siblings' => $f->siblings,
                    'familyType' => $f->family_type,
                    'familyValues' => $f->family_values,
                    'familyStatus' => $f->family_status,
                ];
            }),
            'partnerPrefs' => $this->when($profile?->partnerPreference, function () use ($profile) {
                $p = $profile->partnerPreference;
                return [
                    'ageRange' => $p->age_range,
                    'heightRange' => $p->height_range,
                    'religion' => $p->religion,
                    'community' => $p->community,
                    'education' => $p->education,
                    'profession' => $p->profession,
                    'location' => $p->location,
                    'bloodGroup' => $p->blood_group,
                ];
            }),
        ];
    }
}
