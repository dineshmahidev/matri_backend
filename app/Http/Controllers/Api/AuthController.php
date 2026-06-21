<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfileCompletionService;
use App\Mail\ForgotPasswordMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private ProfileCompletionService $profileCompletion) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'religion' => 'nullable|string',
            'mother_tongue' => 'nullable|string',
            'community' => 'nullable|string',
            'profile_for' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
        ]);

        $settings = \App\Models\SiteSetting::first();
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'role' => 'member',
            'credits' => 0,
            'contact_quota' => $settings ? (int)$settings->free_contact_quota : 0,
            'message_quota' => $settings ? (int)$settings->free_message_quota : 0,
        ]);

        // Create basic profile
        $user->profile()->create([
            'display_id' => 'UK00' . (10000 + $user->id),
            'religion' => $data['religion'] ?? null,
            'community' => $data['community'] ?? null,
            'mother_tongue' => $data['mother_tongue'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => 'India',
        ]);

        // Auto verify email since OTP is removed
        $user->update(['email_verified_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user_id' => $user->id,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_profile_completed' => false,
            ]
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->otp !== $request->otp || $user->otp_expires_at?->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $user->update(['otp' => null, 'otp_expires_at' => null, 'email_verified_at' => now()]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $isProfileCompleted = $this->profileCompletion->isEssentiallyComplete($user);

        return response()->json([
            'message' => 'OTP verified successfully',
            'token' => $token,
            'user' => array_merge($user->only('id', 'name', 'email', 'role'), [
                'is_profile_completed' => (bool)$isProfileCompleted
            ]),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->login)
            ->orWhere('phone', $request->login)
            ->orWhereHas('profile', fn($q) => $q->where('display_id', $request->login))
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $isProfileCompleted = $this->profileCompletion->isEssentiallyComplete($user);

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_id' => $user->profile?->display_id,
                'is_profile_completed' => (bool)$isProfileCompleted,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('profile.gallery', 'profile.familyDetail', 'profile.partnerPreference', 'activeSubscription.plan');
        return new \App\Http\Resources\MemberResource($user);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required_without:phone|email|exists:users,email',
            'phone' => 'required_without:email|string|exists:users,phone',
        ]);

        $user = $request->email
            ? User::where('email', $request->email)->first()
            : User::where('phone', $request->phone)->first();

        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['otp' => $otp, 'otp_expires_at' => now()->addMinutes(10)]);

        // Send OTP via email
        if ($request->email) {
            Mail::to($user->email)->send(new ForgotPasswordMail($user, $otp));
        }

        return response()->json([
            'message' => 'OTP sent successfully.',
            'user_id' => $user->id,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->otp !== $request->otp || $user->otp_expires_at?->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password reset successfully.']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password changed successfully.']);
    }
}
