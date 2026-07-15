<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = ['name', 'address', 'mobile', 'phone', 'email', 'website', 'tax_id', 'logo_url', 'footer_note', 'default_without_signature', 'show_organization_selector', 'bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch', 'bank_routing_number', 'show_bank_info_on_invoice', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'default_without_signature' => 'boolean',
            'show_organization_selector' => 'boolean',
            'show_bank_info_on_invoice' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(PrintLog::class);
    }

    public static function defaultOrganization(): ?self
    {
        return static::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->first();
    }
}
