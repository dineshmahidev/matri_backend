<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$users = User::with(['profile.gallery'])->where('gender', 'female')->take(2)->get();

$exportData = [];

foreach ($users as $user) {
    $profile = $user->profile;
    
    $galleryFiles = [];
    if ($profile && $profile->gallery) {
        foreach ($profile->gallery as $gal) {
            $galleryFiles[] = basename($gal->image_url);
        }
    }

    $exportData[] = [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'gender' => $user->gender,
        'password' => 'password123', // Cannot extract real password, so a placeholder
        'dob' => $user->dob,
        'religion' => $profile->religion ?? null,
        'community' => $profile->community ?? null,
        'city' => $profile->city ?? null,
        'state' => $profile->state ?? null,
        'mother_tongue' => $profile->mother_tongue ?? null,
        'rasi' => $profile->rasi ?? null,
        'nakshatram' => $profile->nakshatram ?? null,
        'profile_pic_filename' => $user->photo ? basename($user->photo) : null,
        'gallery_filenames' => $galleryFiles
    ];
}

file_put_contents('../exported_sample_users.json', json_encode($exportData, JSON_PRETTY_PRINT));
echo "Exported " . count($exportData) . " users to exported_sample_users.json\n";
