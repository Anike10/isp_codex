<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        return view('tickets.index', [
            'tickets' => SupportTicket::with(['customer', 'technician'])->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return view('tickets.create', [
            'customers' => Customer::orderBy('name')->get(),
            'technicians' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        SupportTicket::create($request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'status' => ['required', 'in:open,processing,resolved,closed'],
        ]));

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
    }
}
