<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Interest;
use App\Models\User;
use App\Support\MemberVisibility;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $viewer = $request->user();

        $query = User::where('role', 'member')->whereHas('profile');

        if ($viewer) {
            $query->where('id', '!=', $viewer->id);
        }

        MemberVisibility::applyGenderScope($query, $viewer);

        if ($q = $request->q) {
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('id', 'LIKE', "%{$q}%")
                    ->orWhereHas('profile', fn($p) => $p->where('city', 'LIKE', "%{$q}%")
                        ->orWhere('profession', 'LIKE', "%{$q}%")
                        ->orWhere('education', 'LIKE', "%{$q}%")
                        ->orWhere('community', 'LIKE', "%{$q}%")
                        ->orWhere('display_id', 'LIKE', "%{$q}%"));
            });
        }
        $members = $query->with('profile.gallery', 'activeSubscription.plan')->paginate(24);

        if ($viewer) {
            $memberIds = $members->pluck('id');
            $viewer->load(['savedProfiles' => fn($q) => $q->whereIn('saved_user_id', $memberIds),
                           'unlockedProfiles' => fn($q) => $q->whereIn('unlocked_user_id', $memberIds)]);
            $interestSentIds = Interest::where('sender_id', $viewer->id)->whereIn('receiver_id', $memberIds)->pluck('receiver_id')->toArray();
            $savedIds = $viewer->savedProfiles->pluck('id')->toArray();
            $unlockedIds = $viewer->unlockedProfiles->pluck('id')->toArray();
            $members->each(function ($m) use ($savedIds, $interestSentIds, $unlockedIds) {
                $m->viewer_saved = in_array($m->id, $savedIds);
                $m->viewer_interest_sent = in_array($m->id, $interestSentIds);
                $m->viewer_unlocked = in_array($m->id, $unlockedIds);
            });
        }

        return MemberResource::collection($members);
    }
}
