<?php

namespace App\Http\Controllers;

use App\Models\UserBlock;
use Illuminate\Http\Request;

class UserBlockController extends Controller
{
    public function block(Request $request)
    {
        $request->validate([
            'blocked_id' => 'required|exists:users,id'
        ]);

        if ($request->user()->id == $request->blocked_id) {
            return response()->json(['error' => 'You cannot block yourself.'], 400);
        }

        UserBlock::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $request->blocked_id
        ]);

        return response()->json(['message' => 'User blocked successfully.']);
    }

    public function unblock(Request $request, $blocked_id)
    {
        UserBlock::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $blocked_id)
            ->delete();

        return response()->json(['message' => 'User unblocked successfully.']);
    }

    public function index(Request $request)
    {
        $blocks = UserBlock::with(['blocked', 'blocked.profile'])
            ->where('blocker_id', $request->user()->id)
            ->get();

        $data = $blocks->map(function ($block) {
            $user = $block->blocked;
            if (!$user) return null;

            $profile = $user->profile;
            $photo = $profile?->photo ?? $user->photo ?? null;

            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'photo'      => $photo,
                'gender'     => $user->gender ?? $profile?->gender ?? null,
                'city'       => $profile?->city ?? null,
                'display_id' => $profile?->display_id ?? ('UK' . str_pad($user->id, 6, '0', STR_PAD_LEFT)),
            ];
        })->filter()->values();

        return response()->json(['data' => $data]);
    }
}
