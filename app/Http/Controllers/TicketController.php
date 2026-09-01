<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        return view('tickets.index', [
            'tickets' => SupportTicket::with(['customer', 'technician'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->query('search'));
                    $query->where(function ($query) use ($search) {
                        $query->where('subject', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('connection_id', 'like', "%{$search}%"))
                            ->orWhereHas('technician', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->query('priority')))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
                ->when($request->filled('assigned'), function ($query) use ($request) {
                    $request->query('assigned') === 'unassigned'
                        ? $query->whereNull('assigned_to')
                        : $query->where('assigned_to', $request->integer('assigned'));
                })
                ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
            'technicians' => User::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('tickets.create', [
            'customers' => Customer::orderBy('name')->get(),
            'technicians' => User::orderBy('name')->get(),
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['customer', 'technician', 'replies.user']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'technicians' => User::orderBy('name')->get(),
            'statuses' => SupportTicket::STATUSES,
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
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
        ]));

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Reply added.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $oldStatus = $ticket->status;
        $statusChanged = $oldStatus !== $data['status'];

        $ticket->update([
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        if ($statusChanged || filled($data['note'])) {
            $ticket->replies()->create([
                'user_id' => $request->user()->id,
                'body' => $data['note'] ?? null,
                'old_status' => $statusChanged ? $oldStatus : null,
                'new_status' => $statusChanged ? $data['status'] : null,
            ]);
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket updated.');
    }
}
