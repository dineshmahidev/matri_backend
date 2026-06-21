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

        $user = $request->user();

        $existing = Interest::where('sender_id', $user->id)->where('receiver_id', $request->receiver_id)->first();
        if ($existing) return response()->json(['message' => 'Interest already sent'], 409);

        $settings = \App\Models\SiteSetting::pluck('value', 'key');
        $cost = (int) ($settings['credit_cost_interest'] ?? 1);
        if ($user->credits < $cost) {
            return response()->json([
                'message' => 'Insufficient credits to send interest. Please top up your account.',
                'credits' => $user->credits,
                'is_premium' => $user->isPremium(),
            ], 400);
        }

        $user->decrement('credits', $cost);

        $interest = Interest::create([
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
        ]);

        return response()->json([
            'message' => 'Interest sent',
            'interest' => $interest,
            'credits' => $user->fresh()->credits,
        ], 201);
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
