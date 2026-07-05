<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    // Get my tickets
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->with(['assignedTo'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    // Create ticket
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'category' => 'sometimes|in:technical,billing,course,account,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(uniqid()),
            'user_id' => $request->user()->id,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority ?? 'medium',
            'category' => $request->category ?? 'other',
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully',
            'data' => $ticket
        ], 201);
    }

    // Get ticket details
    public function show(Request $request, $id)
    {
        $ticket = SupportTicket::with(['user', 'assignedTo'])
            ->findOrFail($id);

        // Check if user owns this ticket or is admin
        if ($ticket->user_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    // Admin: Get all tickets
    public function adminIndex(Request $request)
    {
        $query = SupportTicket::with(['user', 'assignedTo']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    // Admin: Assign ticket
    public function assign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'in_progress',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket assigned successfully',
            'data' => $ticket
        ]);
    }

    // Admin: Resolve ticket
    public function resolve(Request $request, $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket resolved successfully',
            'data' => $ticket
        ]);
    }
}
