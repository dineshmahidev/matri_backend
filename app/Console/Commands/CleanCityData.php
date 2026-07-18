<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MemberProfile;

class CleanCityData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:cities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up city JSON data to save just the plain text city name.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $profiles = MemberProfile::all();
        $updatedCount = 0;

        foreach ($profiles as $profile) {
            $cityStr = $profile->city;
            if (!$cityStr) continue;

            // Fix numeric city IDs
            if (is_numeric($cityStr)) {
                $city = \App\Models\City::find($cityStr);
                if ($city) {
                    $profile->city = $city->name;
                    $profile->save();
                    $updatedCount++;
                }
            }

            // Fix numeric state IDs
            $stateStr = $profile->state;
            if (is_numeric($stateStr)) {
                $state = \App\Models\State::find($stateStr);
                if ($state) {
                    $profile->state = $state->name;
                    $profile->save();
                    $updatedCount++;
                }
            }
        }
        $this->info("Cleaned up $updatedCount profiles.");
    }
}
