<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    private const PAYMENT_NOTE_SETTING_KEY = 'invoice_payment_note';
    private const OVERDUE_DISCONNECT_TIME_SETTING_KEY = 'overdue_disconnect_time';

    public function index() { return view('organizations.index', ['organizations' => Organization::orderByDesc('is_default')->orderBy('name')->get()]); }
    public function create()
    {
        return view('organizations.form', [
            'organization' => new Organization,
            'defaultPaymentNote' => $this->defaultPaymentNote(),
            'defaultOverdueDisconnectTime' => $this->defaultOverdueDisconnectTime(),
        ]);
    }
    public function edit(Organization $organization)
    {
        return view('organizations.form', [
            'organization' => $organization,
            'defaultPaymentNote' => $this->defaultPaymentNote(),
            'defaultOverdueDisconnectTime' => $this->defaultOverdueDisconnectTime(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $paymentNote = $data['payment_note'] ?? null;
        $overdueDisconnectTime = $data['overdue_disconnect_time'] ?? null;
        unset($data['payment_note']);
        unset($data['overdue_disconnect_time']);

        DB::transaction(function () use ($data): void {
            if ($data['is_default']) Organization::query()->update(['is_default' => false]);
            Organization::create($data);
        });

        AppSetting::setValue(self::PAYMENT_NOTE_SETTING_KEY, $paymentNote);
        AppSetting::setValue(self::OVERDUE_DISCONNECT_TIME_SETTING_KEY, $overdueDisconnectTime ?? '10:00');
        return redirect()->route('organizations.index')->with('success', 'Organization saved successfully.');
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $this->validated($request);
        $paymentNote = $data['payment_note'] ?? null;
        $overdueDisconnectTime = $data['overdue_disconnect_time'] ?? null;
        unset($data['payment_note']);
        unset($data['overdue_disconnect_time']);

        DB::transaction(function () use ($organization, $data): void {
            if ($data['is_default']) Organization::whereKeyNot($organization->id)->update(['is_default' => false]);
            $organization->update($data);
        });

        AppSetting::setValue(self::PAYMENT_NOTE_SETTING_KEY, $paymentNote);
        AppSetting::setValue(self::OVERDUE_DISCONNECT_TIME_SETTING_KEY, $overdueDisconnectTime ?? '10:00');
        return redirect()->route('organizations.index')->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization)
    {
        if ($organization->is_default) {
            return back()->withErrors(['organization' => 'The default organization cannot be deleted. Make another organization default first.']);
        }

        if ($organization->printLogs()->exists()) {
            return back()->withErrors(['organization' => 'This organization has print history and cannot be deleted. Set it to inactive instead.']);
        }

        $organization->delete();

        return redirect()->route('organizations.index')->with('success', 'Organization deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string'],
            'mobile' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'], 'website' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'], 'logo_url' => ['nullable', 'string', 'max:255'],
            'footer_note' => ['nullable', 'string'],
            'payment_note' => ['nullable', 'string', 'max:5000'],
            'default_without_signature' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:255'], 'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'], 'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_routing_number' => ['nullable', 'string', 'max:100'], 'show_bank_info_on_invoice' => ['nullable', 'boolean'],
            'overdue_disconnect_time' => ['nullable', 'date_format:H:i'],
            'is_default' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $data['default_without_signature'] = $request->boolean('default_without_signature');
        $data['show_organization_selector'] = true;
        $data['show_bank_info_on_invoice'] = $request->boolean('show_bank_info_on_invoice');
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');
        if ($data['is_default']) $data['is_active'] = true;
        return $data;
    }

    private function defaultPaymentNote(): string
    {
        return AppSetting::value(self::PAYMENT_NOTE_SETTING_KEY, '') ?: '';
    }

    private function defaultOverdueDisconnectTime(): string
    {
        $time = AppSetting::value(self::OVERDUE_DISCONNECT_TIME_SETTING_KEY, '10:00');

        return preg_match('/^\d{2}:\d{2}$/', (string) $time) ? $time : '10:00';
    }
}

