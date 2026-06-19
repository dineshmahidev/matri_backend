$user = App\Models\User::whereHas('profile', function($q) { $q->where('display_id', 'UK0010010'); })->first();
if (!$user) { echo "Not found by display_id\n"; exit; }
echo "User ID: {$user->id}\nName: {$user->name}\nEmail: {$user->email}\n";
echo "Role: {$user->role}\nPremium: {$user->profile?->premium}\n";
echo "credits: {$user->credits}\ncontact_quota: {$user->contact_quota}\nmessage_quota: {$user->message_quota}\n";
$sub = $user->activeSubscription;
if ($sub) {
    echo "Active subscription: {$sub->id}, plan_id: {$sub->plan_id}, status: {$sub->status}\n";
    echo "Plan: {$sub->plan?->name}, credits: {$sub->plan?->credits}\n";
} else {
    echo "No active subscription\n";
    $subs = $user->subscriptions;
    if ($subs->count() > 0) {
        echo "Other subscriptions:\n";
        foreach ($subs as $s) echo "  {$s->id} plan_id={$s->plan_id} status={$s->status}\n";
    } else {
        echo "No subscriptions at all\n";
    }
}
