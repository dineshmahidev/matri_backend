<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    // User's own tickets
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($tickets);
    }

    public function show(Request $request, $id)
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json($ticket);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        SupportTicket::create([
            'user_id' => $request->user()->id,
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'open',
        ]);

        return response()->json(['message' => 'Ticket created successfully'], 201);
    }

    // Admin/Staff - list all tickets
    public function adminIndex(Request $request)
    {
        $tickets = SupportTicket::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($tickets);
    }

    // Admin/Staff - view single ticket
    public function adminShow($id)
    {
        $ticket = SupportTicket::with('user')->findOrFail($id);
        return response()->json($ticket);
    }

    // Admin/Staff - reply and/or update status
    public function adminReply(Request $request, $id)
    {
        $data = $request->validate([
            'admin_reply' => 'required|string',
            'status' => 'nullable|string|in:open,closed,in_progress',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->admin_reply = $data['admin_reply'];
        $ticket->replied_at = now();
        if (!empty($data['status'])) {
            $ticket->status = $data['status'];
        }
        $ticket->save();

        return response()->json(['message' => 'Reply sent successfully', 'ticket' => $ticket]);
    }
}
