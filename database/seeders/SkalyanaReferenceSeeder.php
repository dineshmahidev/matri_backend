<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkalyanaReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('cities')->delete();
        DB::table('states')->delete();
        DB::table('castes')->delete();
        DB::table('religions')->delete();

        // Religions
        $religions = [
            [1, 'Hindu'],
            [2, 'Muslim'],
            [3, 'Christian'],
            [4, 'Sikh'],
            [5, 'Buddhist'],
            [6, 'Jain'],
            [7, 'Other'],
        ];
        foreach ($religions as $r) {
            DB::table('religions')->insert(['id' => $r[0], 'name' => $r[1], 'created_at' => now(), 'updated_at' => now()]);
        }

        // Castes (Hindu)
        $castes = [
            [1, 1, 'Iyer'],
            [2, 1, 'Iyengar'],
            [3, 1, 'Reddy'],
            [4, 1, 'Naidu'],
            [5, 1, 'Chettiar'],
            [6, 1, 'Gounder'],
            [7, 1, 'Mudaliar'],
            [8, 1, 'Pillai'],
            [9, 1, 'Thevar'],
            [10, 1, 'Vanniyar'],
            [11, 1, 'Nadar'],
            [12, 1, 'Yadava'],
            [13, 1, 'SC'],
            [14, 1, 'ST'],
            [15, 1, 'BC'],
            [16, 1, 'MBC'],
            [17, 1, 'OC'],
            [18, 1, 'Other'],
        ];
        foreach ($castes as $c) {
            DB::table('castes')->insert(['id' => $c[0], 'religion_id' => $c[1], 'name' => $c[2], 'created_at' => now(), 'updated_at' => now()]);
        }

        // States (only Tamil Nadu)
        DB::table('states')->insert(['id' => 1, 'name' => 'Tamil Nadu', 'created_at' => now(), 'updated_at' => now()]);

        // Cities (Tamil Nadu major cities)
        $cities = [
            'Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem',
            'Tirunelveli', 'Tiruppur', 'Erode', 'Vellore', 'Thoothukudi',
            'Dindigul', 'Thanjavur', 'Ranipet', 'Sivakasi', 'Karur',
            'Udhagamandalam', 'Hosur', 'Nagercoil', 'Kanchipuram', 'Kumbakonam',
            'Cuddalore', 'Rajapalayam', 'Pollachi', 'Bodinayakkanur', 'Arakkonam',
            'Tiruvannamalai', 'Karaikudi', 'Nagapattinam', 'Mettupalayam', 'Pudukkottai',
        ];
        foreach ($cities as $i => $name) {
            DB::table('cities')->insert([
                'id' => $i + 1,
                'state_id' => 1,
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command?->info('Seeded religions, castes, state (Tamil Nadu), and 30 Tamil Nadu cities.');
    }
}
