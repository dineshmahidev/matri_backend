<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PartnerPreference extends Model
{
    protected $fillable = ['member_profile_id', 'age_range', 'height_range', 'religion', 'community', 'education', 'profession', 'location', 'blood_group'];

    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }
}
