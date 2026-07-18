<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;
        $convos = Conversation::where('user_a_id', $userId)->orWhere('user_b_id', $userId)
            ->with(['userA.profile', 'userB.profile'])
            ->latest('updated_at')
            ->get()
            ->map(function ($c) use ($userId) {
                $other = $c->user_a_id === $userId ? $c->userB : $c->userA;
                $unread = $c->user_a_id === $userId ? $c->unread_count_a : $c->unread_count_b;
                return [
                    'id' => $c->id,
                    'memberId' => $other->profile?->display_id,
                    'memberName' => $other->name,
                    'memberPhoto' => $other->profile?->photo,
                    'memberGender' => $other->gender ?? $other->profile?->gender,
                    'online' => (bool) $other->profile?->online,
                    'lastMessage' => $c->last_message,
                    'time' => $c->last_message_time,
                    'unread' => $unread,
                ];
            });

        return response()->json($convos);
    }

    public function messages(Conversation $conversation, Request $request)
    {
        $userId = $request->user()->id;
        if ($conversation->user_a_id !== $userId && $conversation->user_b_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $conversation->messages()->with('sender')->get()->map(fn($m) => [
            'id' => $m->id,
            'from' => $m->sender_id === $userId ? 'me' : 'them',
            'text' => $m->text,
            'imageUrl' => $m->image_url,
            'time' => $m->created_at->format('g:i A'),
        ]);

        return response()->json($messages);
    }

    public function send(Conversation $conversation, Request $request)
    {
        $request->validate([
            'text' => 'required_without:image|nullable|string|max:2000',
            'image' => 'nullable|image|max:5120',
        ]);
        
        $userId = $request->user()->id;

        $user = $request->user();

        // One message restriction (non-premium only)
        if (!$user->isPremium()) {
            $myMessages = $conversation->messages()->where('sender_id', $userId)->count();
            $theirMessages = $conversation->messages()->where('sender_id', '!=', $userId)->count();
            if ($myMessages >= 1 && $theirMessages === 0) {
                return response()->json([
                    'message' => 'You can only send one message until the recipient replies. Once they reply, you can chat unlimitedly!'
                ], 403);
            }

            if ($myMessages === 0) {
                if ($user->message_quota < 1) {
                    return response()->json([
                        'message' => 'Insufficient message quota. Please upgrade your plan to start conversations.'
                    ], 403);
                }
                $user->decrement('message_quota', 1);
            }
        }
        
        $imageUrl = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('messages', 'public');
            $imageUrl = asset('storage/' . $path);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $userId,
            'text' => $request->text,
            'image_url' => $imageUrl,
        ]);

        $conversation->update([
            'last_message' => $request->text ?? 'Sent an image',
            'last_message_time' => now()->diffForHumans(),
        ]);

        return response()->json([
            'id' => $message->id,
            'from' => 'me',
            'text' => $message->text,
            'imageUrl' => $message->image_url,
            'time' => $message->created_at->format('g:i A'),
        ], 201);
    }

    public function sendToUser(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|string',
            'text' => 'required_without:image|nullable|string|max:2000',
            'image' => 'nullable|image|max:5120',
        ]);
        
        $userId = $request->user()->id;
        $receiverId = \App\Models\User::where('id', $request->receiver_id)
            ->orWhereHas('profile', function($q) use ($request) {
                $q->where('display_id', $request->receiver_id);
            })->firstOrFail()->id;

        if ($userId === $receiverId) {
            return response()->json(['message' => 'Cannot send message to yourself'], 400);
        }

        // Check if conversation exists
        $conversation = Conversation::where(function($q) use ($userId, $receiverId) {
            $q->where('user_a_id', $userId)->where('user_b_id', $receiverId);
        })->orWhere(function($q) use ($userId, $receiverId) {
            $q->where('user_a_id', $receiverId)->where('user_b_id', $userId);
        })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_a_id' => $userId,
                'user_b_id' => $receiverId,
                'unread_count_a' => 0,
                'unread_count_b' => 0,
            ]);
        }

        $user = $request->user();

        // One message + quota restrictions (non-premium only)
        if (!$user->isPremium()) {
            $myMessages = $conversation->messages()->where('sender_id', $userId)->count();
            $theirMessages = $conversation->messages()->where('sender_id', '!=', $userId)->count();
            if ($myMessages >= 1 && $theirMessages === 0) {
                return response()->json([
                    'message' => 'You can only send one message until the recipient replies. Once they reply, you can chat unlimitedly!'
                ], 403);
            }

            if ($myMessages === 0) {
                if ($user->message_quota < 1) {
                    return response()->json([
                        'message' => 'Insufficient message quota. Please upgrade your plan to start conversations.'
                    ], 403);
                }
                $user->decrement('message_quota', 1);
            }
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('messages', 'public');
            $imageUrl = asset('storage/' . $path);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $userId,
            'text' => $request->text,
            'image_url' => $imageUrl,
        ]);

        $conversation->update([
            'last_message' => $request->text ?? 'Sent an image',
            'last_message_time' => now()->diffForHumans(),
            'unread_count_' . ($conversation->user_a_id === $receiverId ? 'a' : 'b') => \DB::raw('unread_count_' . ($conversation->user_a_id === $receiverId ? 'a' : 'b') . ' + 1')
        ]);

        return response()->json([
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'from' => 'me',
            'text' => $message->text,
            'imageUrl' => $message->image_url,
            'time' => $message->created_at->format('g:i A'),
        ], 201);
    }
}
