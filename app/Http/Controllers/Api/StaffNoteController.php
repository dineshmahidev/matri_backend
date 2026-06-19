<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffNote;
use Illuminate\Http\Request;

class StaffNoteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = StaffNote::with(['member.profile', 'lead'])
            ->where('staff_id', $user->role === 'staff' ? $user->id : $request->integer('staff_id', $user->id));

        if ($user->role === 'admin' && $request->filled('staff_id')) {
            $query = StaffNote::with(['member.profile', 'lead', 'staff'])
                ->where('staff_id', $request->staff_id);
        } elseif ($user->role === 'admin' && !$request->filled('staff_id')) {
            $query = StaffNote::with(['member.profile', 'lead', 'staff']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhereHas('member', fn ($m) => $m->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('profile', fn ($p) => $p->where('display_id', 'like', "%{$search}%")))
                    ->orWhereHas('lead', fn ($l) => $l->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('follow_up')) {
            match ($request->follow_up) {
                'today' => $query->whereDate('follow_up_at', today()),
                'overdue' => $query->where('follow_up_at', '<', now())->where('status', 'pending'),
                'upcoming' => $query->where('follow_up_at', '>=', now())->where('status', 'pending'),
                default => null,
            };
        }

        if ($request->filled('from')) {
            $query->whereDate('follow_up_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('follow_up_at', '<=', $request->to);
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'nullable|exists:users,id',
            'lead_id' => 'nullable|exists:leads,id',
            'content' => 'required|string|max:5000',
            'follow_up_at' => 'nullable|date',
            'status' => 'nullable|in:pending,completed,cancelled',
        ]);

        if (empty($data['member_id']) && empty($data['lead_id'])) {
            return response()->json(['message' => 'Either member_id or lead_id is required'], 422);
        }

        $note = StaffNote::create([
            ...$data,
            'staff_id' => $request->user()->id,
            'status' => $data['status'] ?? 'pending',
        ]);

        return response()->json($note->load(['member.profile', 'lead']), 201);
    }

    public function update(Request $request, StaffNote $staffNote)
    {
        $this->authorizeNote($request, $staffNote);

        $data = $request->validate([
            'content' => 'sometimes|required|string|max:5000',
            'follow_up_at' => 'nullable|date',
            'status' => 'sometimes|in:pending,completed,cancelled',
        ]);

        $staffNote->update($data);

        return response()->json($staffNote->fresh(['member.profile', 'lead']));
    }

    public function destroy(Request $request, StaffNote $staffNote)
    {
        $this->authorizeNote($request, $staffNote);
        $staffNote->delete();

        return response()->json(['message' => 'Note deleted']);
    }

    private function authorizeNote(Request $request, StaffNote $note): void
    {
        $user = $request->user();
        if ($user->role === 'staff' && $note->staff_id !== $user->id) {
            abort(403, 'Forbidden');
        }
    }
}
