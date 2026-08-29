<?php

namespace Tests\Feature;

use App\Jobs\SendBkashWhatsAppReply;
use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BkashWhatsAppReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '111222333',
            'services.whatsapp.api_version' => 'v21.0',
            'services.whatsapp.payment_template' => 'payment_received',
            'services.whatsapp.payment_template_language' => 'en',
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['name' => 'Ops Admin']);
        $user->permissions()->attach(Permission::where('name', 'manage_payments')->firstOrFail());

        return $user;
    }

    private function enable(array $statuses = ['processed', 'balance']): void
    {
        $wa = app(WhatsAppService::class);
        $wa->setEnabled(true);
        $wa->setNotifyStatuses($statuses);
    }

    public function test_processed_row_queues_a_whatsapp_reply_when_enabled(): void
    {
        Bus::fake();
        $this->enable();

        $customer = Customer::create([
            'name' => 'Rahim', 'phone' => '01711112222', 'connection_id' => 'K-1',
            'address' => 'Kushtia', 'status' => 'active',
        ]);

        $sms = BkashSmsPayment::create([
            'sms_sender' => 'bKash', 'raw_sms' => 'x', 'trx_id' => 'T1', 'ledger_trx_id' => 'T1',
            'amount' => 500, 'status' => 'pending', 'customer_id' => $customer->id,
        ]);

        Bus::assertNotDispatched(SendBkashWhatsAppReply::class);

        $sms->update(['status' => 'processed']);

        Bus::assertDispatched(SendBkashWhatsAppReply::class, fn ($job) => $job->smsPaymentId === $sms->id);
    }

    public function test_no_reply_is_queued_while_the_feature_is_off(): void
    {
        Bus::fake();

        BkashSmsPayment::create([
            'sms_sender' => 'bKash', 'raw_sms' => 'x', 'trx_id' => 'T2', 'ledger_trx_id' => 'T2',
            'amount' => 100, 'status' => 'balance',
        ]);

        Bus::assertNotDispatched(SendBkashWhatsAppReply::class);
    }

    public function test_job_sends_the_template_and_stamps_the_row(): void
    {
        $this->enable();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC']]], 200),
        ]);

        $customer = Customer::create([
            'name' => 'Karim', 'phone' => '01822223333', 'connection_id' => 'K-2',
            'address' => 'Kushtia', 'status' => 'active',
        ]);
        $sms = BkashSmsPayment::create([
            'sms_sender' => 'bKash', 'raw_sms' => 'x', 'trx_id' => 'T3', 'ledger_trx_id' => 'T3',
            'amount' => 750, 'status' => 'processed', 'customer_id' => $customer->id,
        ]);

        (new SendBkashWhatsAppReply($sms->id))->handle(app(WhatsAppService::class));

        $sms->refresh();
        $this->assertSame('sent', $sms->whatsapp_status);
        $this->assertSame('8801822223333', $sms->whatsapp_to);
        $this->assertSame('wamid.ABC', $sms->whatsapp_message_id);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v21.0/111222333/messages')
                && $request['to'] === '8801822223333'
                && $request['template']['name'] === 'payment_received'
                && $request['template']['components'][0]['parameters'][1]['text'] === '750.00'
                && $request['template']['components'][0]['parameters'][2]['text'] === 'T3';
        });
    }

    public function test_job_records_a_failure_from_the_api(): void
    {
        $this->enable();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not found']], 400),
        ]);

        $sms = BkashSmsPayment::create([
            'sms_sender' => 'bKash', 'raw_sms' => 'x', 'trx_id' => 'T4', 'ledger_trx_id' => 'T4',
            'amount' => 200, 'status' => 'processed', 'customer_number' => '01700000000',
        ]);

        (new SendBkashWhatsAppReply($sms->id))->handle(app(WhatsAppService::class));

        $sms->refresh();
        $this->assertSame('failed', $sms->whatsapp_status);
        $this->assertStringContainsString('Template not found', $sms->whatsapp_error);
    }

    public function test_recipient_prefers_the_party_phone_then_the_sms_number(): void
    {
        $wa = app(WhatsAppService::class);

        $withParty = new BkashSmsPayment(['customer_number' => '01700000000']);
        $withParty->setRelation('customer', new Customer(['phone' => '01999998888']));
        $this->assertSame('8801999998888', $wa->recipientFor($withParty));

        $noParty = new BkashSmsPayment(['customer_number' => '8801700000000']);
        $this->assertSame('8801700000000', $wa->recipientFor($noParty));

        $bad = new BkashSmsPayment(['customer_number' => 'not-a-phone']);
        $this->assertNull($wa->recipientFor($bad));
    }

    public function test_settings_form_saves_and_resend_button_sends(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.RE']]], 200)]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('bkash-sms-payments.whatsapp'), [
            'action' => 'save', 'enabled' => '1', 'statuses' => ['processed'],
        ])->assertRedirect()->assertSessionHas('success');

        $wa = app(WhatsAppService::class);
        $this->assertTrue($wa->isEnabled());
        $this->assertSame(['processed'], $wa->notifyStatuses());

        $customer = Customer::create([
            'name' => 'Sent Party', 'phone' => '01755554444', 'connection_id' => 'K-9',
            'address' => 'Kushtia', 'status' => 'active',
        ]);
        $sms = BkashSmsPayment::create([
            'sms_sender' => 'bKash', 'raw_sms' => 'x', 'trx_id' => 'T5', 'ledger_trx_id' => 'T5',
            'amount' => 300, 'status' => 'processed', 'customer_id' => $customer->id,
            'whatsapp_status' => 'failed', 'whatsapp_error' => 'old error',
        ]);

        $this->actingAs($admin)->post(route('bkash-sms-payments.whatsapp-resend', $sms))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('sent', $sms->refresh()->whatsapp_status);
    }
}
