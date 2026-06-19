<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Models\ProfileGallery;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SyncLegacyImages extends Command
{
    protected $signature = 'legacy:sync-images {--dry-run : Preview changes without writing}';

    protected $description = 'Copy legacy profile/gallery images and fix member gender from profiles.json';

    public function handle(): int
    {
        $jsonPath = database_path('seeders/profiles.json');
        if (!File::exists($jsonPath)) {
            $this->error("profiles.json not found at {$jsonPath}. Run scratch/migrate_profiles.js first.");
            return self::FAILURE;
        }

        $profiles = json_decode(File::get($jsonPath), true);
        if (!is_array($profiles)) {
            $this->error('Invalid profiles.json');
            return self::FAILURE;
        }

        $profileAssets = base_path('../assets/images/user/profile');
        $galleryAssets = base_path('../assets/images/user/gallery');
        $dryRun = (bool) $this->option('dry-run');

        Storage::disk('public')->makeDirectory('photos');
        Storage::disk('public')->makeDirectory('gallery');

        $updatedGender = 0;
        $updatedPhotos = 0;
        $updatedGallery = 0;
        $copiedFiles = 0;
        $missingUsers = 0;

        foreach ($profiles as $data) {
            $user = User::where('email', $data['email'])->where('role', 'member')->first();
            if (!$user) {
                $missingUsers++;
                continue;
            }

            $gender = $data['gender'] ?? null;
            if (in_array($gender, ['male', 'female'], true) && $user->gender !== $gender) {
                if (!$dryRun) {
                    $user->update(['gender' => $gender]);
                }
                $updatedGender++;
                $this->line("Gender: {$user->email} → {$gender}");
            }

            $photoFile = $data['profile_data']['photo'] ?? null;
            if (!$photoFile) {
                continue;
            }

            $this->copyLegacyFile(
                [$profileAssets . DIRECTORY_SEPARATOR . $photoFile],
                'photos/' . $photoFile,
                $dryRun,
                $copiedFiles
            );

            $profile = $user->profile;
            if (!$profile) {
                continue;
            }

            $photoUrl = asset('storage/photos/' . $photoFile);
            if ($profile->photo !== $photoUrl) {
                if (!$dryRun) {
                    $profile->update(['photo' => $photoUrl]);
                }
                $updatedPhotos++;
            }

            $galleryFiles = array_values(array_unique(array_merge(
                [$photoFile],
                $data['gallery'] ?? []
            )));

            if (!$dryRun) {
                $profile->gallery()->delete();
            }

            foreach ($galleryFiles as $sortOrder => $file) {
                $storageFolder = ($sortOrder === 0 || $file === $photoFile) ? 'photos' : 'gallery';

                if ($storageFolder === 'gallery') {
                    $this->copyLegacyFile(
                        [
                            $galleryAssets . DIRECTORY_SEPARATOR . $file,
                            $profileAssets . DIRECTORY_SEPARATOR . $file,
                        ],
                        'gallery/' . $file,
                        $dryRun,
                        $copiedFiles
                    );
                }

                $imageUrl = asset('storage/' . $storageFolder . '/' . $file);

                if (!$dryRun) {
                    ProfileGallery::create([
                        'member_profile_id' => $profile->id,
                        'image_url' => $imageUrl,
                        'sort_order' => $sortOrder,
                    ]);
                }
                $updatedGallery++;
            }
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete.' : 'Legacy image sync complete.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Profiles in JSON', count($profiles)],
                ['Users not found in DB', $missingUsers],
                ['Genders updated', $updatedGender],
                ['Profile photos updated', $updatedPhotos],
                ['Gallery rows written', $updatedGallery],
                ['Files copied', $copiedFiles],
            ]
        );

        return self::SUCCESS;
    }

    private function copyLegacyFile(array $sources, string $destRelative, bool $dryRun, int &$copiedFiles): void
    {
        if (Storage::disk('public')->exists($destRelative)) {
            return;
        }

        foreach ($sources as $source) {
            if (!File::exists($source)) {
                continue;
            }

            if (!$dryRun) {
                Storage::disk('public')->put($destRelative, File::get($source));
            }
            $copiedFiles++;
            return;
        }
    }
}
