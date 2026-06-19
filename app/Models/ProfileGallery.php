<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProfileGallery extends Model
{
    protected $fillable = ['member_profile_id', 'image_url', 'sort_order'];

    public function profile()
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }
}
