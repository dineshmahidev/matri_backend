<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'credits', 'contact_quota', 'message_quota', 'phone', 'company_mobile', 'salary', 'gender', 'dob', 'tob', 'otp', 'otp_expires_at', 'photo',
    ];

    protected $hidden = [
        'password', 'remember_token', 'otp', 'otp_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'salary' => 'decimal:2',
            'otp_expires_at' => 'datetime',
        ];
    }

    public function profile()
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentInterests()
    {
        return $this->hasMany(Interest::class, 'sender_id');
    }

    public function receivedInterests()
    {
        return $this->hasMany(Interest::class, 'receiver_id');
    }

    public function savedProfiles()
    {
        return $this->belongsToMany(User::class, 'saved_profiles', 'user_id', 'saved_user_id')->withTimestamps();
    }

    public function assignedLeads()
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function staffAttendance()
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function unlockedProfiles()
    {
        return $this->belongsToMany(User::class, 'unlocked_profiles', 'user_id', 'unlocked_user_id')->withTimestamps();
    }

    public function isPremium(): bool
    {
        return (bool) ($this->profile?->premium ?? $this->activeSubscription?->status === 'active');
    }
}
