<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MemberProfile;
use App\Models\ProfileGallery;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImportUsersFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users {json_file} {image_folder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a JSON file and map their profile and gallery images from a folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonFile = $this->argument('json_file');
        $imageFolder = rtrim($this->argument('image_folder'), '\\/');

        if (!file_exists($jsonFile)) {
            $this->error("JSON file not found: {$jsonFile}");
            return 1;
        }

        if (!is_dir($imageFolder)) {
            $this->error("Image folder not found: {$imageFolder}");
            return 1;
        }

        $jsonContent = file_get_contents($jsonFile);
        $users = json_decode($jsonContent, true);

        if (!is_array($users)) {
            $this->error("Invalid JSON format.");
            return 1;
        }

        $this->info("Starting import of " . count($users) . " users...");

        foreach ($users as $userData) {
            $this->info("Importing user: {$userData['email']}");

            // Handle Profile Image
            $photoUrl = null;
            if (!empty($userData['profile_pic_filename'])) {
                $sourcePath = $imageFolder . DIRECTORY_SEPARATOR . $userData['profile_pic_filename'];
                if (file_exists($sourcePath)) {
                    // Generate a unique name
                    $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
                    $newFilename = 'admin-photos/' . Str::random(40) . '.' . $ext;
                    
                    // Copy to storage/app/public/admin-photos
                    Storage::disk('public')->put($newFilename, file_get_contents($sourcePath));
                    $photoUrl = asset('storage/' . $newFilename);
                } else {
                    $this->warn("Profile picture not found: {$sourcePath}");
                }
            }

            // Create or Update User
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'] ?? null,
                    'phone' => $userData['phone'] ?? null,
                    'gender' => $userData['gender'] ?? null,
                    'password' => Hash::make($userData['password'] ?? 'password'),
                    'dob' => $userData['dob'] ?? null,
                    'role' => 'member',
                    'photo' => $photoUrl,
                ]
            );

            // Create or Update MemberProfile
            $profile = MemberProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_id' => 'M' . strtoupper(Str::random(6)),
                    'religion' => $userData['religion'] ?? null,
                    'community' => $userData['community'] ?? null,
                    'city' => $userData['city'] ?? null,
                    'state' => $userData['state'] ?? null,
                    'mother_tongue' => $userData['mother_tongue'] ?? null,
                    'rasi' => $userData['rasi'] ?? null,
                    'nakshatram' => $userData['nakshatram'] ?? null,
                    'photo' => $photoUrl,
                ]
            );

            // Handle Gallery Images
            if (!empty($userData['gallery_filenames']) && is_array($userData['gallery_filenames'])) {
                $sortOrder = 1;
                foreach ($userData['gallery_filenames'] as $galleryFilename) {
                    $sourcePath = $imageFolder . DIRECTORY_SEPARATOR . $galleryFilename;
                    if (file_exists($sourcePath)) {
                        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
                        $newFilename = 'gallery-photos/' . Str::random(40) . '.' . $ext;
                        
                        Storage::disk('public')->put($newFilename, file_get_contents($sourcePath));
                        
                        ProfileGallery::create([
                            'member_profile_id' => $profile->id,
                            'image_url' => asset('storage/' . $newFilename),
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        $this->warn("Gallery picture not found: {$sourcePath}");
                    }
                }
            }

            $this->info("Successfully imported user ID: {$user->id}");
        }

        $this->info("Import completed.");
        return 0;
    }
}
