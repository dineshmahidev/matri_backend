<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interest;
use Illuminate\Http\Request;
use App\Http\Resources\MemberResource;

class SavedProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $saved = $user->savedProfiles()->with('profile.gallery', 'activeSubscription.plan')->get();
        $memberIds = $saved->pluck('id');
        $user->load(['savedProfiles' => fn($q) => $q->whereIn('saved_user_id', $memberIds),
                     'unlockedProfiles' => fn($q) => $q->whereIn('unlocked_user_id', $memberIds)]);
        $interestSentIds = Interest::where('sender_id', $user->id)->whereIn('receiver_id', $memberIds)->pluck('receiver_id')->toArray();
        $unlockedIds = $user->unlockedProfiles->pluck('id')->toArray();
        $saved->each(function ($m) use ($unlockedIds, $interestSentIds) {
            $m->viewer_saved = true;
            $m->viewer_interest_sent = in_array($m->id, $interestSentIds);
            $m->viewer_unlocked = in_array($m->id, $unlockedIds);
        });
        return MemberResource::collection($saved);
    }

    public function store(Request $request)
    {
        $request->validate(['saved_user_id' => 'required|exists:users,id']);
        $request->user()->savedProfiles()->syncWithoutDetaching([$request->saved_user_id]);
        return response()->json(['message' => 'Profile saved'], 201);
    }

    public function destroy(Request $request, $userId)
    {
        $request->user()->savedProfiles()->detach($userId);
        return response()->json(['message' => 'Profile removed from saved']);
    }
}
