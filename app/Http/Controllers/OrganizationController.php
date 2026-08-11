<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationController extends Controller
{
    public function index() { return view('organizations.index', ['organizations' => Organization::orderByDesc('is_default')->orderBy('name')->get()]); }
    public function create() { return view('organizations.form', ['organization' => new Organization]); }
    public function edit(Organization $organization) { return view('organizations.form', compact('organization')); }

    public function store(Request $request)
    {
        $organization = DB::transaction(function () use ($request) {
            $data = $this->validated($request);
            if ($data['is_default']) Organization::query()->update(['is_default' => false]);
            return Organization::create($data);
        });
        return redirect()->route('organizations.index')->with('success', 'Organization saved successfully.');
    }

    public function update(Request $request, Organization $organization)
    {
        DB::transaction(function () use ($request, $organization) {
            $data = $this->validated($request);
            if ($data['is_default']) Organization::whereKeyNot($organization->id)->update(['is_default' => false]);
            $organization->update($data);
        });
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
            'default_without_signature' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:255'], 'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'], 'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_routing_number' => ['nullable', 'string', 'max:100'], 'show_bank_info_on_invoice' => ['nullable', 'boolean'],
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
}
