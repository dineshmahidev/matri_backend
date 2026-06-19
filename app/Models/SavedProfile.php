<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SavedProfile extends Model
{
    protected $fillable = ['user_id', 'saved_user_id'];

    public function user() { return $this->belongsTo(User::class); }
    public function savedUser() { return $this->belongsTo(User::class, 'saved_user_id'); }
}
