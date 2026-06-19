<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MemberProfile;
use App\Models\ProfileGallery;
use App\Models\FamilyDetail;
use App\Models\PartnerPreference;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    private function pick(array $arr, int $i)
    {
        return $arr[$i % count($arr)];
    }

    public function run(): void
    {
        // 1. Create Admin
        if (!User::where('email', 'admin@matrimony.in')->exists()) {
            User::create([
                'name' => 'Admin User',
                'email' => 'admin@matrimony.in',
                'role' => 'admin',
                'phone' => '+91 9999999999',
                'gender' => 'male',
                'dob' => '1990-01-01',
                'tob' => '09:00',
                'password' => Hash::make('password'),
            ]);
        }

        // 2. Create Staff users
        $staffNames = ["Ravi Kumar", "Meera Sharma", "Anil Patel", "Sunita Rao", "Vikram Singh", "Pooja Joshi"];
        foreach ($staffNames as $i => $name) {
            $staffEmail = "staff{$i}@matrimony.in";
            if (!User::where('email', $staffEmail)->exists()) {
                User::create([
                    'name' => $name,
                    'email' => $staffEmail,
                    'role' => 'staff',
                    'phone' => '+91 888888880' . $i,
                    'gender' => $i % 2 === 0 ? 'male' : 'female',
                    'dob' => '1992-05-15',
                    'tob' => '10:30',
                    'password' => Hash::make('password'),
                ]);
            }
        }

        // 3. Create 100 Members from the migrated profiles JSON file
        $jsonPath = database_path('seeders/profiles.json');
        if (file_exists($jsonPath)) {
            $profiles = json_decode(file_get_contents($jsonPath), true);
            foreach ($profiles as $i => $data) {
                // Check if user already exists
                if (User::where('email', $data['email'])->exists()) {
                    continue;
                }
                // Create user
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => 'member',
                    'phone' => $data['phone'],
                    'gender' => $data['gender'],
                    'dob' => $data['dob'],
                    'tob' => $data['tob'],
                    'password' => $data['password_hash'] ?: Hash::make('password'),
                    'contact_quota' => 5,   // default free quotas
                    'message_quota' => 15,
                ]);

                // Create Member Profile
                $profile = MemberProfile::create([
                    'user_id' => $user->id,
                    'display_id' => 'UK00' . (10000 + $user->id),
                    'age' => $data['profile_data']['age'],
                    'height' => $data['profile_data']['height'],
                    'religion' => $data['profile_data']['religion'],
                    'community' => $data['profile_data']['community'],
                    'mother_tongue' => $data['profile_data']['mother_tongue'],
                    'city' => $data['profile_data']['city'],
                    'state' => $data['profile_data']['state'],
                    'country' => $data['profile_data']['country'],
                    'profession' => $data['profile_data']['profession'],
                    'education' => $data['profile_data']['education'],
                    'income' => $data['profile_data']['income'],
                    'marital_status' => $data['profile_data']['marital_status'],
                    'photo' => asset('storage/photos/' . $data['profile_data']['photo']),
                    'bio' => $data['profile_data']['bio'],
                    'premium' => $i % 3 === 0, // Make 1 in every 3 members premium
                    'verified' => true,
                    'online' => $i % 4 === 0,
                    'last_active_at' => $i % 4 === 0 ? Carbon::now() : Carbon::now()->subHours(($i % 8) + 1),
                    'rasi' => $data['profile_data']['rasi'],
                    'nakshatram' => $data['profile_data']['nakshatram'],
                ]);

                // Create Gallery Photos
                $galleryFiles = array_values(array_unique(array_merge(
                    [$data['profile_data']['photo']],
                    $data['gallery'] ?? []
                )));

                foreach ($galleryFiles as $sortOrder => $file) {
                    $folder = ($sortOrder === 0 || $file === $data['profile_data']['photo']) ? 'photos' : 'gallery';
                    ProfileGallery::create([
                        'member_profile_id' => $profile->id,
                        'image_url' => asset('storage/' . $folder . '/' . $file),
                        'sort_order' => $sortOrder,
                    ]);
                }

                // Create Family Details
                FamilyDetail::create([
                    'member_profile_id' => $profile->id,
                    'father' => $data['family_detail']['father'],
                    'mother' => $data['family_detail']['mother'],
                    'siblings' => $data['family_detail']['siblings'],
                    'family_type' => $data['family_detail']['family_type'],
                    'family_values' => $data['family_detail']['family_values'],
                    'family_status' => $data['family_detail']['family_status'],
                ]);

                // Create Partner Preferences
                PartnerPreference::create([
                    'member_profile_id' => $profile->id,
                    'age_range' => $data['partner_preference']['age_range'],
                    'height_range' => $data['partner_preference']['height_range'],
                    'religion' => $data['partner_preference']['religion'],
                    'community' => $data['partner_preference']['community'],
                    'education' => $data['partner_preference']['education'],
                    'profession' => $data['partner_preference']['profession'],
                    'location' => $data['partner_preference']['location'],
                ]);
            }
        } else {
            $this->command->error("Profiles JSON file not found at: {$jsonPath}");
        }
    }
}
