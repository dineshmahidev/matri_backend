<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['user_a_id', 'user_b_id', 'last_message', 'last_message_time', 'unread_count_a', 'unread_count_b'];

    public function userA() { return $this->belongsTo(User::class, 'user_a_id'); }
    public function userB() { return $this->belongsTo(User::class, 'user_b_id'); }
    public function messages() { return $this->hasMany(Message::class)->orderBy('created_at'); }
}
