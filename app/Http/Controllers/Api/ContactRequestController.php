<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\Notification;
use Illuminate\Http\Request;

class ContactRequestController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate(['target_id' => 'required|integer|exists:users,id']);

        $user = $request->user();
        $targetId = (int) $data['target_id'];

        if ($user->id === $targetId) {
            return response()->json(['message' => 'Cannot request your own contact'], 400);
        }

        // Check for existing request (any status)
        $existing = ContactRequest::where('requester_id', $user->id)
            ->where('target_id', $targetId)
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                return response()->json(['message' => 'Contact request already sent and pending'], 409);
            }
            if ($existing->status === 'accepted') {
                return response()->json(['message' => 'Contact already accessible'], 409);
            }
            // If rejected, allow re-send by updating
            $existing->update(['status' => 'pending']);
            return response()->json(['message' => 'Contact request re-sent', 'request' => $existing->fresh()->load('target')]);
        }

        // Deduct from contact_quota
        if ($user->contact_quota < 1) {
            return response()->json(['message' => 'Insufficient contact view credits. Please upgrade to send contact requests.'], 400);
        }

        $user->decrement('contact_quota');

        $contactRequest = ContactRequest::create([
            'requester_id' => $user->id,
            'target_id' => $targetId,
            'status' => 'pending',
        ]);

        // Notify target
        Notification::create([
            'user_id' => $targetId,
            'title' => 'Contact Request',
            'description' => "{$user->name} has sent you a contact request.",
            'type' => 'contact_request',
            'read' => false,
        ]);

        return response()->json([
            'message' => 'Contact request sent',
            'request' => $contactRequest->load('target'),
            'contact_quota' => $user->fresh()->contact_quota,
        ], 201);
    }

    public function sent(Request $request)
    {
        $requests = ContactRequest::with('target.profile')
            ->where('requester_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($requests);
    }

    public function received(Request $request)
    {
        $requests = ContactRequest::with('requester.profile')
            ->where('target_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($requests);
    }

    public function respond(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|in:accepted,rejected']);

        $contactRequest = ContactRequest::with('requester')->where('target_id', $request->user()->id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        $contactRequest->update(['status' => $data['status']]);

        if ($data['status'] === 'accepted') {
            // Also create the reverse unlock so both can see each other's contact
            $contactRequest->requester->unlockedProfiles()->syncWithoutDetaching([$contactRequest->target_id]);
            $request->user()->unlockedProfiles()->syncWithoutDetaching([$contactRequest->requester_id]);

            // Notify requester
            Notification::create([
                'user_id' => $contactRequest->requester_id,
                'title' => 'Contact Request Accepted',
                'description' => "{$request->user()->name} has accepted your contact request.",
                'type' => 'contact_request',
                'read' => false,
            ]);
        }

        return response()->json(['message' => 'Contact request ' . $data['status'], 'request' => $contactRequest->fresh()->load('requester.profile')]);
    }

    public function check(Request $request, $targetId)
    {
        $user = $request->user();

        $existing = ContactRequest::where('requester_id', $user->id)
            ->where('target_id', $targetId)
            ->first();

        $received = ContactRequest::where('target_id', $user->id)
            ->where('requester_id', $targetId)
            ->where('status', 'accepted')
            ->exists();

        if ($existing) {
            return response()->json([
                'exists' => true,
                'status' => $existing->status,
                'request_id' => $existing->id,
                'can_view' => $existing->status === 'accepted' || $received,
            ]);
        }

        // Check if the other user accepted my request (reverse direction)
        $reverseAccepted = ContactRequest::where('requester_id', $targetId)
            ->where('target_id', $user->id)
            ->where('status', 'accepted')
            ->exists();

        return response()->json([
            'exists' => false,
            'status' => null,
            'request_id' => null,
            'can_view' => $reverseAccepted,
        ]);
    }
}
