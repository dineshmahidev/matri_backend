<?php

namespace App\Services;

use App\Models\Caste;
use App\Models\City;
use App\Models\Religion;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberUserService
{
    public function validateProfileFields(array $data): array
    {
        $profile = [];

        if (!empty($data['religion_id'])) {
            $religion = Religion::find($data['religion_id']);
            if (!$religion) {
                throw ValidationException::withMessages(['religion_id' => ['Invalid religion selected.']]);
            }
            $profile['religion'] = $religion->name;
        } elseif (!empty($data['religion'])) {
            $profile['religion'] = $data['religion'];
        }

        if (!empty($data['caste_id'])) {
            $caste = Caste::find($data['caste_id']);
            if (!$caste) {
                throw ValidationException::withMessages(['caste_id' => ['Invalid caste selected.']]);
            }
            if (!empty($data['religion_id']) && (int) $caste->religion_id !== (int) $data['religion_id']) {
                throw ValidationException::withMessages(['caste_id' => ['Caste does not belong to the selected religion.']]);
            }
            $profile['community'] = $caste->name;
        } elseif (!empty($data['community'])) {
            $profile['community'] = $data['community'];
        }

        if (!empty($data['state_id'])) {
            $state = State::find($data['state_id']);
            if (!$state) {
                throw ValidationException::withMessages(['state_id' => ['Invalid state selected.']]);
            }
            $profile['state'] = $state->name;
        } elseif (!empty($data['state'])) {
            $profile['state'] = $data['state'];
        }

        if (!empty($data['city_id'])) {
            $city = City::find($data['city_id']);
            if (!$city) {
                throw ValidationException::withMessages(['city_id' => ['Invalid city selected.']]);
            }
            if (!empty($data['state_id']) && (int) $city->state_id !== (int) $data['state_id']) {
                throw ValidationException::withMessages(['city_id' => ['City does not belong to the selected state.']]);
            }
            $profile['city'] = $city->name;
        } elseif (!empty($data['city'])) {
            $profile['city'] = $data['city'];
        }

        if (!empty($data['mother_tongue'])) {
            $profile['mother_tongue'] = $data['mother_tongue'];
        }

        if (!empty($data['dob'])) {
            $profile['age'] = abs(now()->diffInYears($data['dob']));
        }

        return $profile;
    }

    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'role' => 'member',
            'email_verified_at' => $data['email_verified_at'] ?? now(),
            'credits' => 0,
            'contact_quota' => 0,
            'message_quota' => 0,
        ]);

        $profileData = array_merge([
            'display_id' => 'UK00' . (10000 + $user->id),
            'country' => 'India',
        ], $this->validateProfileFields($data));

        $user->profile()->create($profileData);

        return $user->fresh(['profile']);
    }
}
