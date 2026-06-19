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
}
