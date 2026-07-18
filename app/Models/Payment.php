<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['user_id', 'subscription_id', 'invoice_id', 'plan_label', 'amount', 'status', 'paid_at', 'notes', 'razorpay_order_id', 'razorpay_payment_id'];
    protected function casts(): array { return ['paid_at' => 'datetime']; }

    public function user() { return $this->belongsTo(User::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
}
