<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserHasPermission;
use App\Models\Customer;
use App\Models\OltOnu;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketReplyTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(): SupportTicket
    {
        $customer = Customer::create([
            'name' => 'Ticket Party',
            'phone' => '01700000000',
            'connection_id' => 'TKT-1',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);

        return SupportTicket::create([
            'customer_id' => $customer->id,
            'subject' => 'No internet',
            'description' => 'Down since morning',
            'priority' => 'high',
            'status' => 'open',
        ]);
    }

    public function test_reply_is_stored_and_shown(): void
    {
        $ticket = $this->ticket();
        $user = User::factory()->create();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->post(route('tickets.replies.store', $ticket), ['body' => 'Technician dispatched'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Technician dispatched',
            'new_status' => null,
        ]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Technician dispatched');
    }

    public function test_ticket_list_shows_the_party_olt_onu_in_one_column(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'name' => 'Onu Party',
            'phone' => '01700000009',
            'connection_id' => 'TKT-ONU',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'last_connected_mac' => 'AA:BB:CC:DD:EE:77',
        ]);

        OltOnu::query()->create([
            'olt_name' => 'US_EPON',
            'pon_port' => 7,
            'onu_id' => 31,
            'mac_address' => 'AA:BB:CC:DD:EE:77',
            'status' => 'online',
            'last_live_polled_at' => now(),
        ]);

        SupportTicket::create([
            'customer_id' => $customer->id,
            'subject' => 'Weak signal',
            'description' => 'Rx dropping',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('US_EPON - 7/31');
    }

    public function test_ticket_map_page_shows_party_and_ticket_details(): void
    {
        $user = User::factory()->create();
        $ticket = $this->ticket();
        $ticket->customer->update([
            'phone' => '01812345678',
            'map_latitude' => 23.9013,
            'map_longitude' => 89.1220,
        ]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->get(route('tickets.map', $ticket))
            ->assertOk()
            ->assertSee('Ticket #'.$ticket->id.' — Map &amp; Details', false)
            ->assertSee($ticket->subject)
            ->assertSee('01812345678')
            ->assertSee('data-customer-location-map', false)
            ->assertSee(route('tickets.show', $ticket), false);
    }

    public function test_ticket_list_and_details_expose_reply_and_update_controls(): void
    {
        $ticket = $this->ticket();
        $user = User::factory()->create();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee(route('tickets.show', $ticket), false)
            ->assertSee('Reply / Update');

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee(route('tickets.status.update', $ticket), false)
            ->assertSee(route('tickets.replies.store', $ticket), false)
            ->assertSee('Save Update')
            ->assertSee('Send Reply');
    }

    public function test_ticket_create_lists_use_the_global_writable_search_component(): void
    {
        $this->ticket();
        $user = User::factory()->create(['name' => 'Searchable Technician']);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('data-search-placeholder="পার্টির নাম, সংযোগ আইডি বা ধরন লিখুন"', false)
            ->assertSee('data-search-placeholder="Authorized-এর নাম লিখুন"', false)
            ->assertSee('searchable-select-menu', false)
            ->assertSee('searchableSelectComponent', false)
            ->assertSee('Searchable Technician');
    }

    public function test_status_update_changes_ticket_and_logs_the_change(): void
    {
        $ticket = $this->ticket();
        $technician = User::factory()->create();
        $user = User::factory()->create();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('tickets.status.update', $ticket), [
                'status' => 'resolved',
                'assigned_to' => $technician->id,
                'note' => 'Splice repaired',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertSame($technician->id, $ticket->assigned_to);

        $this->assertDatabaseHas('ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'old_status' => 'open',
            'new_status' => 'resolved',
            'body' => 'Splice repaired',
        ]);
    }

    public function test_status_update_without_change_or_note_adds_no_log(): void
    {
        $ticket = $this->ticket();
        $user = User::factory()->create();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('tickets.status.update', $ticket), ['status' => 'open']);

        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_reply_body_is_required(): void
    {
        $ticket = $this->ticket();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs(User::factory()->create())
            ->post(route('tickets.replies.store', $ticket), ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    public function test_bulk_status_update_sets_status_and_logs_each_change(): void
    {
        $user = User::factory()->create();

        $open = $this->ticket();
        $customer = $open->customer;
        $alsoOpen = SupportTicket::create([
            'customer_id' => $customer->id,
            'subject' => 'Second issue',
            'description' => 'Also down',
            'priority' => 'normal',
            'status' => 'open',
        ]);
        $alreadyResolved = SupportTicket::create([
            'customer_id' => $customer->id,
            'subject' => 'Old issue',
            'description' => 'Fixed last week',
            'priority' => 'low',
            'status' => 'resolved',
        ]);

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('tickets.bulk-status.update'), [
                'ids' => [$open->id, $alsoOpen->id, $alreadyResolved->id],
                'status' => 'resolved',
                'note' => 'Batch resolved after area maintenance',
            ])
            ->assertRedirect();

        $this->assertSame('resolved', $open->fresh()->status);
        $this->assertSame('resolved', $alsoOpen->fresh()->status);

        // The two that changed get a status-change reply; all three get the note.
        $this->assertDatabaseHas('ticket_replies', [
            'support_ticket_id' => $open->id,
            'old_status' => 'open',
            'new_status' => 'resolved',
            'body' => 'Batch resolved after area maintenance',
        ]);
        $this->assertSame(1, $alreadyResolved->replies()->count());
        $this->assertNull($alreadyResolved->replies()->first()->new_status);
        $this->assertSame('Batch resolved after area maintenance', $alreadyResolved->replies()->first()->body);
    }

    public function test_bulk_status_update_requires_ids_and_a_valid_status(): void
    {
        $user = User::factory()->create();
        $ticket = $this->ticket();

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('tickets.bulk-status.update'), ['status' => 'resolved'])
            ->assertSessionHasErrors('ids');

        $this->withoutMiddleware(EnsureUserHasPermission::class)
            ->actingAs($user)
            ->patch(route('tickets.bulk-status.update'), ['ids' => [$ticket->id], 'status' => 'nope'])
            ->assertSessionHasErrors('status');
    }
}
