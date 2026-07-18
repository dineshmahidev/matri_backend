<?php

namespace App\Services;

use App\Models\User;
use App\Models\MemberProfile;
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

        // Create new user
        $user = User::create([
            'name' => $data['name'] ?? 'Unknown',
            'email' => $email,
            'password' => Hash::make($generatedPassword),
            'phone' => $phone,
            'gender' => $data['gender'] ?? 'female',
            'dob' => $data['dob'] ?? null,
            'role' => 'member',
            'photo' => $data['profile_pic_filename'] ?? null
        ]);

        // Create Member Profile
        MemberProfile::create([
            'user_id' => $user->id,
            'gender' => $data['gender'] ?? 'female',
            'religion' => $data['religion'] ?? null,
            'caste' => $data['community'] ?? null,
            'mother_tongue' => $data['mother_tongue'] ?? null,
            'city' => $data['city'] ?? null,
        ]);

        // Handle Photo Copy
        if (isset($data['profile_pic_filename']) && $data['profile_pic_filename']) {
            $destPath = "public/admin-photos/" . $data['profile_pic_filename'];
            
            if (!Storage::exists($destPath)) {
                if (file_exists($imageSourcePath)) {
                    Storage::put($destPath, file_get_contents($imageSourcePath));
                }
            }
        }

        return ['status' => 'imported'];
    }
}
