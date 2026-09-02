<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerOnuPowerSample;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\OnuSignalTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        // No status filter chosen => hide closed tickets by default.
        // `status=all` shows every status; a specific status filters to it.
        $status = trim((string) $request->query('status', ''));

        return view('tickets.index', [
            'tickets' => SupportTicket::with(['customer', 'technician'])
                ->when($status === '', fn ($query) => $query->where('status', '!=', 'closed'))
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
                ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
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
        return $this->createView();
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
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
        ]);

        // Snapshot the party's current ONU Rx so the ticket keeps a "signal at
        // open" reading; it is also the first "last update" value.
        $rx = $this->latestOnuRx((int) $data['customer_id']);
        $data['rx_power_on_create'] = $rx;
        $data['rx_power_on_update'] = $rx;
        $data['rx_power_updated_at'] = now();

        SupportTicket::create($data);

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
    }

    /** The party's most recent non-null ONU Rx laser power, in dBm. */
    private function latestOnuRx(int $customerId): ?float
    {
        $value = CustomerOnuPowerSample::query()
            ->where('customer_id', $customerId)
            ->whereNotNull('rx_power_dbm')
            ->orderByDesc('sampled_at')
            ->value('rx_power_dbm');

        return $value === null ? null : (float) $value;
    }

    /** Re-snapshot the party's ONU Rx whenever a ticket is touched. */
    private function refreshOnuRx(SupportTicket $ticket): void
    {
        $ticket->update([
            'rx_power_on_update' => $this->latestOnuRx((int) $ticket->customer_id),
            'rx_power_updated_at' => now(),
        ]);
    }

    public function createFromOnuSignal(
        Request $request,
        Customer $customer,
        OnuSignalTicketService $tickets
    ) {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'swing' => ['nullable', 'numeric', 'min:0.1', 'max:40'],
        ]);

        $from = isset($data['from'])
            ? Carbon::parse($data['from'])->startOfDay()
            : Carbon::today()->subDays(7)->startOfDay();
        $to = isset($data['to'])
            ? Carbon::parse($data['to'])->endOfDay()
            : Carbon::today()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $swing = max(0.1, (float) ($data['swing'] ?? 3.0));
        $ticketDefaults = $tickets->draft($customer, $from, $to, $swing);

        if (! $ticketDefaults) {
            return back()->with('warning', 'নির্বাচিত সময়ে এই পার্টির কোনো ONU সিগন্যাল নমুনা পাওয়া যায়নি।');
        }

        return $this->createView($ticketDefaults, route('troubleshoot.onu-signal', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'swing' => $swing,
        ]));
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

        $this->refreshOnuRx($ticket);

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

        $this->refreshOnuRx($ticket);

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

    private function createView(array $ticketDefaults = [], ?string $backUrl = null)
    {
        return view('tickets.create', [
            'customers' => Customer::orderBy('name')->get(),
            'technicians' => User::orderBy('name')->get(),
            'ticketDefaults' => $ticketDefaults,
            'isOnuDraft' => $ticketDefaults !== [],
            'backUrl' => $backUrl ?? route('tickets.index'),
        ]);
    }
}
