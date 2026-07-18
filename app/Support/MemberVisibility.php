<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MemberVisibility
{
    public static function oppositeGender(?User $viewer): ?string
    {
        if (!$viewer?->isMember()) {
            return null;
        }

        return match ($viewer->gender) {
            'male' => 'female',
            'female' => 'male',
            default => null,
        };
    }

    /**
     * Logged-in members only see opposite-gender profiles.
     * Guests and staff/admin may use an explicit gender filter.
     */
    public static function applyGenderScope(Builder $query, ?User $viewer, ?string $requestedGender = null): Builder
    {
        $opposite = self::oppositeGender($viewer);

        if ($opposite) {
            return $query->where('gender', $opposite);
        }

        if ($requestedGender) {
            $query->where('gender', $requestedGender);
        }

        return $query;
    }

    /**
     * Exclude users that the viewer has blocked, and users who have blocked the viewer.
     */
    public static function applyBlockScope(Builder $query, ?User $viewer): Builder
    {
        if ($viewer) {
            $query->whereNotIn('id', function($q) use ($viewer) {
                $q->select('blocked_id')->from('user_blocks')->where('blocker_id', $viewer->id);
            })->whereNotIn('id', function($q) use ($viewer) {
                $q->select('blocker_id')->from('user_blocks')->where('blocked_id', $viewer->id);
            });
        }
        return $query;
    }
}
