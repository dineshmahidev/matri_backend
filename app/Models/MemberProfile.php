<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberProfile extends Model
{
    protected $fillable = [
        'user_id', 'display_id', 'age', 'height', 'blood_group', 'religion', 'community',
        'mother_tongue', 'city', 'state', 'country', 'profession', 'education',
        'income', 'marital_status', 'photo', 'bio', 'premium', 'verified',
        'online', 'last_active_at', 'rasi', 'nakshatram', 'featured',
    ];

    protected function casts(): array
    {
        return [
            'premium' => 'boolean',
            'verified' => 'boolean',
            'featured' => 'boolean',
            'online' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gallery()
    {
        return $this->hasMany(ProfileGallery::class)->orderBy('sort_order');
    }

    public function familyDetail()
    {
        return $this->hasOne(FamilyDetail::class);
    }

    public function partnerPreference()
    {
        return $this->hasOne(PartnerPreference::class);
    }

    public function scopePremium($query)
    {
        return $query->where('premium', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('online', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
