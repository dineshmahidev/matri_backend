<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MemberProfile;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1 Admin
        if (!User::where('email', 'admin@matrimony.in')->exists()) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@matrimony.in',
                'role' => 'admin',
                'phone' => '+91 9999999999',
                'gender' => 'male',
                'dob' => '1990-01-01',
                'tob' => '09:00',
                'password' => Hash::make('password'),
            ]);
            $this->createMemberProfile($admin, 'UK00001');
        }

        // 3 Staff
        $staffData = [
            ['name' => 'Ravi Kumar', 'email' => 'staff0@matrimony.in', 'phone' => '+91 8888888800', 'gender' => 'male'],
            ['name' => 'Meera Sharma', 'email' => 'staff1@matrimony.in', 'phone' => '+91 8888888801', 'gender' => 'female'],
            ['name' => 'Anil Patel', 'email' => 'staff2@matrimony.in', 'phone' => '+91 8888888802', 'gender' => 'male'],
        ];
        foreach ($staffData as $i => $s) {
            if (!User::where('email', $s['email'])->exists()) {
                $user = User::create([
                    'name' => $s['name'],
                    'email' => $s['email'],
                    'role' => 'staff',
                    'phone' => $s['phone'],
                    'gender' => $s['gender'],
                    'dob' => '1992-05-15',
                    'tob' => '10:30',
                    'password' => Hash::make('password'),
                ]);
                $this->createMemberProfile($user, 'UK00' . (10001 + $user->id));
            }
        }

        // 8 Member users
        $members = [
            ['name' => 'Priya Venkatesh', 'gender' => 'female', 'age' => 26, 'religion' => 'Hindu', 'community' => 'Iyer', 'city' => 'Chennai', 'profession' => 'Software Engineer', 'education' => 'B.E'],
            ['name' => 'Arun Kumar', 'gender' => 'male', 'age' => 29, 'religion' => 'Hindu', 'community' => 'Mudaliar', 'city' => 'Coimbatore', 'profession' => 'Doctor', 'education' => 'MBBS'],
            ['name' => 'Divya Rajan', 'gender' => 'female', 'age' => 24, 'religion' => 'Hindu', 'community' => 'Pillai', 'city' => 'Madurai', 'profession' => 'Teacher', 'education' => 'B.Ed'],
            ['name' => 'Suresh Babu', 'gender' => 'male', 'age' => 31, 'religion' => 'Hindu', 'community' => 'Gounder', 'city' => 'Salem', 'profession' => 'Business', 'education' => 'MBA'],
            ['name' => 'Lakshmi Narayanan', 'gender' => 'female', 'age' => 27, 'religion' => 'Hindu', 'community' => 'Chettiar', 'city' => 'Tiruchirappalli', 'profession' => 'Chartered Accountant', 'education' => 'CA'],
            ['name' => 'Karthick Raja', 'gender' => 'male', 'age' => 28, 'religion' => 'Hindu', 'community' => 'Nadar', 'city' => 'Tirunelveli', 'profession' => 'Civil Engineer', 'education' => 'B.Tech'],
            ['name' => 'Anitha Devi', 'gender' => 'female', 'age' => 25, 'religion' => 'Hindu', 'community' => 'Thevar', 'city' => 'Vellore', 'profession' => 'Nurse', 'education' => 'B.Sc Nursing'],
            ['name' => 'Manoj Kumar', 'gender' => 'male', 'age' => 30, 'religion' => 'Hindu', 'community' => 'Reddy', 'city' => 'Hosur', 'profession' => 'IT Manager', 'education' => 'M.Sc'],
        ];

        foreach ($members as $i => $m) {
            $email = 'user' . ($i + 1) . '@test.in';
            if (User::where('email', $email)->exists()) continue;

            $user = User::create([
                'name' => $m['name'],
                'email' => $email,
                'role' => 'member',
                'phone' => '+91 98765432' . str_pad((string)($i + 10), 2, '0', STR_PAD_LEFT),
                'gender' => $m['gender'],
                'dob' => now()->subYears($m['age'])->subDays(rand(1, 365))->format('Y-m-d'),
                'tob' => sprintf('%02d:%02d', rand(6, 22), rand(0, 3) * 15),
                'password' => Hash::make('password'),
                'contact_quota' => 5,
                'message_quota' => 15,
            ]);

            $profile = $this->createMemberProfile($user, 'UK00' . (10001 + $user->id));

            MemberProfile::where('id', $profile->id)->update([
                'age' => $m['age'],
                'religion' => $m['religion'],
                'community' => $m['community'],
                'city' => $m['city'],
                'profession' => $m['profession'],
                'education' => $m['education'],
                'premium' => $i < 3,
                'verified' => true,
            ]);
        }

        $this->command?->info('Seeded 1 admin, 3 staff, and 8 member users.');
    }

    private function createMemberProfile($user, string $displayId)
    {
        return MemberProfile::create([
            'user_id' => $user->id,
            'display_id' => $displayId,
            'age' => 25,
            'height' => rand(150, 185),
            'religion' => 'Hindu',
            'community' => 'Other',
            'mother_tongue' => 'Tamil',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'country' => 'India',
            'profession' => 'Professional',
            'education' => 'Graduate',
            'income' => 'Rs 5-10 Lakhs',
            'marital_status' => 'Never Married',
            'bio' => 'Looking for a caring and understanding partner.',
            'premium' => false,
            'verified' => true,
            'online' => false,
            'rasi' => 'Mesha',
            'nakshatram' => 'Ashwini',
        ]);
    }
}
