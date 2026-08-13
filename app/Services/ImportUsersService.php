<?php

namespace App\Services;

use App\Models\User;
use App\Models\MemberProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ImportUsersService
{
    /**
     * Import a single user record
     *
     * @param array $data
     * @param string $imageSourcePath The full path to the image in the extracted zip
     * @return array
     */
    public function import(array $data, string $imageSourcePath): array
    {
        $email = $data['email'] ?? null;

        if (!$email) {
            return ['status' => 'skipped'];
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            return ['status' => 'skipped'];
        }

        // Generate dynamic password
        $emailPrefix = substr($email, 0, 3);
        $phoneSuffix = isset($data['phone']) && strlen($data['phone']) >= 4
            ? substr($data['phone'], -4)
            : '1234';

        $generatedPassword = strtolower($emailPrefix) . '@' . $phoneSuffix;

        // Sanitize phone (DB column usually varchar(15) or (20))
        $phone = isset($data['phone']) ? substr(trim($data['phone']), 0, 20) : null;

        $user = DB::transaction(function () use ($data, $generatedPassword, $phone, $imageSourcePath) {
            $user = User::create([
                'name' => $data['name'] ?? 'Unknown',
                'email' => $data['email'],
                'password' => Hash::make($generatedPassword),
                'phone' => $phone,
                'gender' => isset($data['gender']) ? strtolower($data['gender']) : 'female',
                'dob' => $data['dob'] ?? null,
                'role' => 'member',
                'email_verified_at' => now(),
            ]);

            $profileData = [
                'user_id' => $user->id,
                'display_id' => 'UK00' . (10000 + $user->id),
                'religion' => $data['religion'] ?? null,
                'community' => $data['community'] ?? $data['caste'] ?? null,
                'mother_tongue' => $data['mother_tongue'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => 'India',
                'profession' => $data['profession'] ?? null,
                'education' => $data['education'] ?? null,
                'income' => $data['income'] ?? null,
                'marital_status' => $data['marital_status'] ?? 'Never Married',
                'height' => $data['height'] ?? null,
                'bio' => $data['bio'] ?? null,
            ];

            if (!empty($data['dob'])) {
                $profileData['age'] = abs(now()->diffInYears($data['dob']));
            }

            // Handle Photo Copy
            if (!empty($data['profile_pic_filename'])) {
                $filename = basename($data['profile_pic_filename']);
                $destPath = 'admin-photos/' . $filename;

                if (!Storage::disk('public')->exists($destPath) && file_exists($imageSourcePath)) {
                    Storage::disk('public')->put($destPath, file_get_contents($imageSourcePath));
                }

                if (Storage::disk('public')->exists($destPath)) {
                    $profileData['photo'] = asset('storage/' . $destPath);
                }
            }

            MemberProfile::create($profileData);

            return $user;
        });

        return ['status' => 'imported'];
    }
}