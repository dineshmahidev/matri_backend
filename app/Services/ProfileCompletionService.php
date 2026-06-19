<?php

namespace App\Services;

use App\Models\User;

class ProfileCompletionService
{
    public function isEssentiallyComplete(User $user): bool
    {
        return count($this->missingFields($user)) === 0;
    }

    public function missingFields(User $user): array
    {
        $profile = $user->profile;
        $missing = [];

        if (!$user->dob) {
            $missing[] = 'date_of_birth';
        }
        if (!$profile?->religion) {
            $missing[] = 'religion';
        }
        if (!$profile?->community) {
            $missing[] = 'caste';
        }
        if (!$profile?->city) {
            $missing[] = 'city';
        }
        if (!$profile?->state) {
            $missing[] = 'state';
        }
        if (!$profile?->education) {
            $missing[] = 'education';
        }
        if (!$profile?->photo) {
            $missing[] = 'profile_photo';
        }

        return $missing;
    }

    public function completionPercent(User $user): int
    {
        $total = 7;
        $filled = $total - count($this->missingFields($user));

        return (int) round(($filled / $total) * 100);
    }
}
