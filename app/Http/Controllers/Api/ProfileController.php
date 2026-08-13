<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('profile.gallery', 'profile.familyDetail', 'profile.partnerPreference', 'activeSubscription.plan');
        return new MemberResource($user);
    }

    public function privacy(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'photo_visibility' => $user->photo_visibility ?? 'all',
            'profile_visibility' => $user->profile_visibility ?? 'all',
            'show_phone' => (bool) ($user->show_phone ?? false),
            'notify_interest' => (bool) ($user->notify_interest ?? true),
            'notify_message' => (bool) ($user->notify_message ?? true),
        ]);
    }

    public function updatePrivacy(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'photo_visibility' => 'sometimes|in:all,members,none',
            'profile_visibility' => 'sometimes|in:all,members,none',
            'show_phone' => 'sometimes|boolean',
            'notify_interest' => 'sometimes|boolean',
            'notify_message' => 'sometimes|boolean',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Privacy settings saved',
            'photo_visibility' => $user->photo_visibility,
            'profile_visibility' => $user->profile_visibility,
            'show_phone' => (bool) $user->show_phone,
            'notify_interest' => (bool) $user->notify_interest,
            'notify_message' => (bool) $user->notify_message,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'dob' => 'sometimes|date|nullable',
            'bio' => 'sometimes|string|max:1000',
            'smoking_status' => 'sometimes|nullable|in:yes,no',
            'drinking_status' => 'sometimes|nullable|in:yes,no',
            'disability' => 'sometimes|nullable|in:yes,no',
            'height' => 'sometimes|nullable|string',
            'blood_group' => 'sometimes|string|nullable',
            'skin_colour' => 'sometimes|string|nullable',
            'religion' => 'sometimes|string',
            'community' => 'sometimes|string',
            'mother_tongue' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'profession' => 'sometimes|string',
            'education' => 'sometimes|string',
            'income' => 'sometimes|string',
            'marital_status' => 'sometimes|string',
            'family' => 'sometimes|array',
            'family.father' => 'sometimes|string|nullable',
            'family.mother' => 'sometimes|string|nullable',
            'family.siblings' => 'sometimes|string|nullable',
            'family.family_type' => 'sometimes|string|nullable',
            'family.family_values' => 'sometimes|string|nullable',
            'family.family_status' => 'sometimes|string|nullable',
            'partner_preferences' => 'sometimes|array',
            'partner_preferences.age_range' => 'sometimes|string|nullable',
            'partner_preferences.height_range' => 'sometimes|string|nullable',
            'partner_preferences.religion' => 'sometimes|string|nullable',
            'partner_preferences.community' => 'sometimes|string|nullable',
            'partner_preferences.education' => 'sometimes|string|nullable',
            'partner_preferences.profession' => 'sometimes|string|nullable',
            'partner_preferences.location' => 'sometimes|string|nullable',
            'partner_preferences.blood_group' => 'sometimes|string|nullable',
        ]);

        if (isset($data['name'])) $user->update(['name' => $data['name']]);
        if (isset($data['phone'])) $user->update(['phone' => $data['phone']]);
        if (array_key_exists('dob', $data)) {
            $user->update(['dob' => $data['dob']]);
            if ($data['dob'] && $profile) {
                $profile->update(['age' => \Carbon\Carbon::parse($data['dob'])->age]);
            }
        }
        if (isset($data['family']) && $profile) {
            $profile->familyDetail()->updateOrCreate([], $data['family']);
        }

        if (isset($data['partner_preferences']) && $profile) {
            $profile->partnerPreference()->updateOrCreate([], $data['partner_preferences']);
        }

        $profileData = collect($data)->except(['name', 'phone', 'dob', 'family', 'partner_preferences'])->toArray();
        if ($profile && count($profileData) > 0) {
            $profile->update($profileData);
        }

        return response()->json(['message' => 'Profile updated']);
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:5120',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $this->storeOptimizedImage($file, 'photos');
            $imageUrl = asset('storage/' . $path);

            // Optional: delete old photo
            if ($profile->photo) {
                $parsedUrl = parse_url($profile->photo, PHP_URL_PATH);
                if ($parsedUrl) {
                    $storagePath = str_replace('/storage/', '', $parsedUrl);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($storagePath);
                }
            }

            $profile->update(['photo' => $imageUrl]);

            return response()->json(['message' => 'Profile photo updated', 'photo' => $imageUrl]);
        }

        return response()->json(['message' => 'No photo provided'], 400);
    }

    public function addGalleryImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $this->storeOptimizedImage($file, 'gallery');
            $imageUrl = asset('storage/' . $path);

            $sortOrder = $profile->gallery()->count();

            \App\Models\ProfileGallery::create([
                'member_profile_id' => $profile->id,
                'image_url' => $imageUrl,
                'sort_order' => $sortOrder,
            ]);

            return response()->json(['message' => 'Image added to gallery', 'image' => $imageUrl], 201);
        }

        return response()->json(['message' => 'No image provided'], 400);
    }

    public function addGalleryImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1|max:6',
            'images.*' => 'required|image|max:5120',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $currentCount = $profile->gallery()->count();
        $isPremium = $user->isPremium();
        $maxTotal = $isPremium ? 12 : 3;

        $files = $request->file('images');
        $newCount = count($files);

        if (($currentCount + $newCount) > $maxTotal) {
            $allowed = $maxTotal - $currentCount;
            return response()->json([
                'message' => "You can only add $allowed more image(s). Your limit is $maxTotal.",
                'current' => $currentCount,
                'limit' => $maxTotal,
                'allowed' => $allowed,
            ], 422);
        }

        $uploaded = [];

        foreach ($files as $file) {
            $path = $this->storeOptimizedImage($file, 'gallery');
            $imageUrl = asset('storage/' . $path);
            $sortOrder = $currentCount++;

            \App\Models\ProfileGallery::create([
                'member_profile_id' => $profile->id,
                'image_url' => $imageUrl,
                'sort_order' => $sortOrder,
            ]);

            $uploaded[] = $imageUrl;
        }

        return response()->json([
            'message' => count($uploaded) . ' image(s) added to gallery',
            'images' => $uploaded,
        ], 201);
    }

    public function deleteGalleryImage(Request $request)
    {
        $request->validate([
            'image_url' => 'required|string',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $galleryItem = \App\Models\ProfileGallery::where('member_profile_id', $profile->id)
            ->where('image_url', $request->image_url)
            ->first();

        if (!$galleryItem) {
            return response()->json(['message' => 'Image not found in gallery'], 404);
        }

        // Delete the file from local storage
        $parsedUrl = parse_url($request->image_url, PHP_URL_PATH);
        if ($parsedUrl) {
            $storagePath = str_replace('/storage/', '', $parsedUrl);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($storagePath);
        }

        $galleryItem->delete();

        return response()->json(['message' => 'Image deleted from gallery']);
    }

    public function updateOnboardingStep(Request $request)
    {
        $request->validate([
            'step' => 'required|integer|min:0|max:10',
        ]);

        $user = $request->user();
        $user->update(['onboarding_step' => $request->step]);

        return response()->json([
            'message' => 'Onboarding step updated',
            'onboarding_step' => $user->onboarding_step,
        ]);
    }

    private function storeOptimizedImage($file, string $folder): string
    {
        $maxWidth = 1000;
        $jpegQuality = 75;

        if (function_exists('imagecreatefromstring')) {
            $contents = file_get_contents($file->getRealPath());
            $src = @imagecreatefromstring($contents);

            if ($src) {
                $width = imagesx($src);
                $height = imagesy($src);

                $newWidth = $width;
                $newHeight = $height;

                if ($width > $maxWidth) {
                    $newWidth = $maxWidth;
                    $newHeight = (int) round($height * ($maxWidth / $width));
                }

                $dst = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                // Only re-encode if smaller than original or already over maxWidth
                $filename = uniqid('img_') . '.jpg';
                $fullPath = storage_path('app/public/' . $folder . '/' . $filename);

                if (!is_dir(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0755, true);
                }

                imagejpeg($dst, $fullPath, $jpegQuality);
                imagedestroy($dst);
                imagedestroy($src);

                return $folder . '/' . $filename;
            }
        }

        return $file->store($folder, 'public');
    }
}

