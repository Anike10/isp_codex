<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserHasPermission;
use App\Models\Customer;
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
}
