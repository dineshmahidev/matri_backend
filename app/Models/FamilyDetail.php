<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FamilyDetail extends Model
{
    protected $fillable = ['member_profile_id', 'father', 'mother', 'siblings', 'family_type', 'family_values', 'family_status'];

    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }
}
