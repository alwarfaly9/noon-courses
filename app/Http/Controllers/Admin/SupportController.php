<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * List support tickets.
     */
    public function index(Request $request)
    {
        $query = SupportTicket::with(['user', 'assignedTo']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(20);
        return view('admin.support', compact('tickets'));
    }
}
