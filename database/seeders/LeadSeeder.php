<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;

class LeadSeeder extends Seeder
{
    private function pick(array $arr, int $i)
    {
        return $arr[$i % count($arr)];
    }

    public function run(): void
    {
        $namesF = ["Aanya Sharma","Priya Iyer","Riya Mehta","Saanvi Kapoor","Ishita Reddy","Aisha Khan","Neha Patel","Diya Singh","Kavya Nair","Anika Gupta"];
        $namesM = ["Aarav Verma","Vivaan Joshi","Arjun Rao","Rohan Malhotra","Kabir Bose","Aditya Pillai","Karthik Menon","Dhruv Saxena","Ishan Bhatt","Rahul Chowdhury"];
        $allNames = array_merge($namesF, $namesM);

        $sources = ["Facebook Ad", "Google", "Referral", "Organic", "Instagram"];
        $statuses = ["New", "Contacted", "Qualified", "Converted", "Lost"];

        $staff = User::where('role', 'staff')->get();

        for ($i = 0; $i < 12; $i++) {
            $displayId = 'L' . (2000 + $i);
            if (Lead::where('display_id', $displayId)->exists()) continue;

            $name = $this->pick($allNames, $i);
            $staffUser = $staff->count() > 0 ? $this->pick($staff->all(), $i) : null;

            Lead::create([
                'display_id' => $displayId,
                'name' => $name,
                'phone' => '+91 9' . substr(str_pad((string)(800000000 + $i * 13771), 9, '0', STR_PAD_LEFT), 0, 9),
                'email' => "lead{$i}@example.com",
                'source' => $this->pick($sources, $i),
                'status' => $this->pick($statuses, $i),
                'assigned_to' => $staffUser ? $staffUser->id : null,
                'created_at' => Carbon::create(2026, 6, ($i % 28) + 1),
            ]);
        }
    }
}
