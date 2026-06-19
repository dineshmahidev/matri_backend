<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\StaffAttendance;
use App\Services\MemberUserService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function __construct(private MemberUserService $memberUserService) {}

    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'dob' => $user->dob?->format('Y-m-d'),
            'role' => $user->role,
            'photo' => $user->photo,
            'createdAt' => $user->created_at?->format('Y-m-d'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|nullable|string|max:20',
            'gender' => 'sometimes|nullable|string',
            'dob' => 'sometimes|nullable|date',
        ]);
        $user->update($data);
        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function uploadProfilePhoto(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:5120']);
        $path = $request->file('photo')->store('staff-photos', 'public');
        $user = $request->user();
        $user->photo = asset('storage/' . $path);
        $user->save();
        return response()->json(['url' => $user->photo]);
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $leadsQuery = Lead::where('assigned_to', $user->id);

        $stats = (clone $leadsQuery)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as contacted', ['Contacted'])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as converted', ['Converted'])
            ->first();

        $todayAttendance = StaffAttendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        return response()->json([
            'name' => $user->name,
            'stats' => [
                'assigned' => (int) ($stats->total ?? 0),
                'contacted' => (int) ($stats->contacted ?? 0),
                'converted' => (int) ($stats->converted ?? 0),
            ],
            'attendance' => [
                'checked_in' => (bool) ($todayAttendance?->login_at),
                'checked_out' => (bool) ($todayAttendance?->logout_at),
                'login_at' => $todayAttendance?->login_at?->format('H:i:s'),
                'logout_at' => $todayAttendance?->logout_at?->format('H:i:s'),
            ],
            'leads' => $leadsQuery->latest()->take(8)->get(),
        ]);
    }

    public function checkIn(Request $request)
    {
        $user = $request->user();
        $attendance = StaffAttendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => today()],
            ['login_at' => now()]
        );

        if (!$attendance->login_at) {
            $attendance->update(['login_at' => now()]);
        }

        if ($attendance->logout_at) {
            return response()->json(['message' => 'Already checked out for today'], 422);
        }

        return response()->json([
            'message' => 'Checked in successfully',
            'attendance' => $attendance->fresh(),
        ]);
    }

    public function checkOut(Request $request)
    {
        $user = $request->user();
        $attendance = StaffAttendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        if (!$attendance || !$attendance->login_at) {
            return response()->json(['message' => 'You must check in first'], 422);
        }

        if ($attendance->logout_at) {
            return response()->json(['message' => 'Already checked out for today'], 422);
        }

        $attendance->update(['logout_at' => now()]);

        return response()->json([
            'message' => 'Checked out successfully',
            'attendance' => $attendance->fresh(),
        ]);
    }

    public function attendance(Request $request)
    {
        $user = $request->user();
        $today = StaffAttendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        $history = StaffAttendance::where('user_id', $user->id)
            ->orderByDesc('date')
            ->take(30)
            ->get()
            ->map(fn ($a) => [
                'date' => $a->date->format('Y-m-d'),
                'login_at' => $a->login_at?->format('H:i:s'),
                'logout_at' => $a->logout_at?->format('H:i:s'),
                'notes' => $a->notes,
            ]);

        return response()->json([
            'today' => $today ? [
                'date' => $today->date->format('Y-m-d'),
                'login_at' => $today->login_at?->format('H:i:s'),
                'logout_at' => $today->logout_at?->format('H:i:s'),
                'checked_in' => (bool) $today->login_at,
                'checked_out' => (bool) $today->logout_at,
            ] : null,
            'history' => $history,
        ]);
    }

    public function leads(Request $request)
    {
        return response()->json(
            Lead::where('assigned_to', $request->user()->id)->latest()->paginate(20)
        );
    }

    public function showLead(Lead $lead, Request $request)
    {
        if ($lead->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($lead->load('notes.author:id,name'));
    }

    public function updateLead(Lead $lead, Request $request)
    {
        if ($lead->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['status' => 'required|in:New,Contacted,Qualified,Converted,Lost']);
        $lead->update(['status' => $request->status]);
        return response()->json(['message' => 'Lead updated', 'lead' => $lead]);
    }

    public function leadNotes(Lead $lead, Request $request)
    {
        if ($lead->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = LeadNote::where('lead_id', $lead->id)->with('author:id,name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn ($l) => $l->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function storeLeadNote(Lead $lead, Request $request)
    {
        if ($lead->assigned_to !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'note' => 'required|string|max:5000',
            'follow_up_at' => 'nullable|date',
            'status' => 'nullable|in:pending,completed,cancelled',
        ]);

        $note = LeadNote::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'note' => $data['note'],
            'follow_up_at' => $data['follow_up_at'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        return response()->json([
            'message' => 'Note saved',
            'note' => $note->load('author:id,name'),
        ], 201);
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'password' => 'required|string|min:8',
            'dob' => 'nullable|date',
            'religion_id' => 'nullable|integer|exists:religions,id',
            'religion' => 'required_without:religion_id|string|max:100',
            'caste_id' => 'nullable|integer|exists:castes,id',
            'community' => 'required_without:caste_id|string|max:100',
            'state_id' => 'nullable|integer|exists:states,id',
            'state' => 'required_without:state_id|string|max:100',
            'city_id' => 'nullable|integer|exists:cities,id',
            'city' => 'required_without:city_id|string|max:100',
            'mother_tongue' => 'nullable|string|max:50',
            'rasi' => 'nullable|string|max:50',
            'nakshatram' => 'nullable|string|max:50',
        ]);

        $user = $this->memberUserService->create($data);

        return response()->json(['message' => 'User created', 'user_id' => $user->id], 201);
    }

    public function monthlyReport(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $attendance = StaffAttendance::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $leads = Lead::where('assigned_to', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy(fn ($l) => $l->created_at?->format('Y-m-d'));

        $days = [];
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $att = $attendance->get($key);
            $dayLeads = $leads->get($key, collect());

            $days[] = [
                'date' => $key,
                'login_at' => $att?->login_at?->format('H:i:s'),
                'logout_at' => $att?->logout_at?->format('H:i:s'),
                'checked_in' => (bool) $att?->login_at,
                'checked_out' => (bool) $att?->logout_at,
                'new_leads' => $dayLeads->count(),
                'converted' => $dayLeads->where('status', 'Converted')->count(),
                'contacted' => $dayLeads->where('status', 'Contacted')->count(),
                'completed' => $dayLeads->whereIn('status', ['Converted', 'Qualified'])->count(),
                'pending' => $dayLeads->where('status', 'New')->count(),
            ];
        }

        $totalLeads = collect($days)->sum('new_leads');
        $totalConverted = collect($days)->sum('converted');

        return response()->json([
            'month' => $month,
            'days' => $days,
            'summary' => [
                'total_leads' => $totalLeads,
                'total_converted' => $totalConverted,
                'total_contacted' => collect($days)->sum('contacted'),
                'total_completed' => collect($days)->sum('completed'),
                'total_pending' => collect($days)->sum('pending'),
                'conversion_rate' => $totalLeads > 0 ? round(($totalConverted / $totalLeads) * 100, 1) : 0,
                'days_worked' => collect($days)->filter(fn ($d) => $d['checked_in'])->count(),
            ],
        ]);
    }
}
