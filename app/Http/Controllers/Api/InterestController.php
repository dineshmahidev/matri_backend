<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interest;
use Illuminate\Http\Request;
use App\Http\Resources\MemberResource;

class InterestController extends Controller
{
    public function sent(Request $request)
    {
        $interests = $request->user()->sentInterests()->with('receiver.profile.gallery', 'receiver.activeSubscription.plan')->latest()->paginate(20);
        return response()->json($interests->through(fn($i) => [
            'id' => $i->id,
            'status' => $i->status,
            'sent_at' => $i->created_at->diffForHumans(),
            'member' => new MemberResource($i->receiver),
        ]));
    }

    public function received(Request $request)
    {
        $interests = $request->user()->receivedInterests()->with('sender.profile.gallery', 'sender.activeSubscription.plan')->latest()->paginate(20);
        return response()->json($interests->through(fn($i) => [
            'id' => $i->id,
            'status' => $i->status,
            'received_at' => $i->created_at->diffForHumans(),
            'member' => new MemberResource($i->sender),
        ]));
    }

    public function send(Request $request)
    {
        $request->validate(['receiver_id' => 'required|exists:users,id']);

        $existing = Interest::where('sender_id', $request->user()->id)->where('receiver_id', $request->receiver_id)->first();
        if ($existing) return response()->json(['message' => 'Interest already sent'], 409);

        $interest = Interest::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
        ]);

        return response()->json(['message' => 'Interest sent', 'interest' => $interest], 201);
    }

    public function respond(Request $request, Interest $interest)
    {
        $request->validate(['status' => 'required|in:accepted,rejected']);

        if ($interest->receiver_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $interest->update(['status' => $request->status]);
        return response()->json(['message' => 'Interest ' . $request->status]);
    }
}
