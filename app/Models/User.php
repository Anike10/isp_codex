<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'entry_by',
        'name',
        'email',
        'password',
        'reseller_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * A super admin sits above the permission/menu system entirely and is the
     * only account allowed to grant or revoke super admin on other users.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function deniedPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user_denials');
    }

    public function menuAccesses(): HasMany
    {
        return $this->hasMany(UserMenuAccess::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'reseller_id');
    }

    public function defaultPaymentAccount(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'default_payment_account_id');
    }

    public function ownedPaymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class, 'owner_user_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if (! $this->relationLoaded('deniedPermissions')) {
            $this->load('deniedPermissions');
        }

        if ($this->deniedPermissions->contains('name', $permission)) {
            return false;
        }

        if (! $this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        if ($this->permissions->contains('name', $permission)) {
            return true;
        }

        if (! $this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        } elseif ($this->roles->contains(fn (Role $role) => ! $role->relationLoaded('permissions'))) {
            $this->load('roles.permissions');
        }

        return $this->roles->contains(fn (Role $role) => $role->permissions->contains('name', $permission));
    }

    public function canAccessMenu(string $menuKey): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $definition = collect(config('user_access.menu_groups', []))
            ->pluck('items')
            ->collapse()
            ->get($menuKey);

        if (! is_array($definition) || empty($definition['permission'])) {
            return false;
        }

        if (! $this->relationLoaded('menuAccesses')) {
            $this->load('menuAccesses');
        }

        $override = $this->menuAccesses->firstWhere('menu_key', $menuKey);

        if ($override && ! $override->allowed) {
            return false;
        }

        $permissionNames = $definition['permissions'] ?? [$definition['permission']];

        return collect($permissionNames)->contains(fn (string $permission): bool => $this->hasPermission($permission));
    }
}
