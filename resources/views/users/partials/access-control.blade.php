@php
    $permissionsByName = $permissions->keyBy('name');
    $groupedPermissionNames = collect($permissionGroups)
        ->flatMap(fn ($group) => array_keys($group['items'] ?? []))
        ->all();
    $otherPermissions = $permissions->reject(fn ($permission) => in_array($permission->name, $groupedPermissionNames, true));
@endphp

<style>
    .access-layout { display:grid; grid-template-columns:minmax(260px, 360px) minmax(0, 1fr); gap:16px; align-items:start; }
    .access-panels { min-width:0; }
    .profile-panel { position:sticky; top:84px; }
    .section-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:12px; }
    .section-head h2 { margin-bottom:4px; }
    .mini-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .mini-btn { border:1px solid var(--line); background:#f8fafc; color:var(--ink); border-radius:6px; padding:7px 10px; cursor:pointer; font-weight:700; font-size:12px; }
    .mini-btn:hover { background:#eef4fb; }
    .choice-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:10px; }
    .choice-card { border:1px solid var(--line); border-radius:8px; padding:12px; display:flex; gap:10px; align-items:flex-start; background:#fff; cursor:pointer; min-height:58px; }
    .choice-card:hover { border-color:#9db4cf; background:#f8fbff; }
    .choice-card input, .permission-menu-group input { width:auto; margin-top:3px; flex:0 0 auto; }
    .choice-title { display:block; font-weight:700; line-height:1.25; }
    .choice-sub { display:block; margin-top:3px; color:var(--muted); font-size:12px; line-height:1.35; word-break:break-word; }
    .access-search { margin-bottom:12px; }
    .access-count { color:var(--muted); font-size:13px; }
    .role-help { margin-top:12px; padding:10px 12px; border-radius:7px; background:#f8fafc; color:var(--muted); font-size:12px; line-height:1.45; }
    .permission-tree { display:grid; gap:12px; }
    .permission-menu-group { border:1px solid var(--line); border-radius:9px; overflow:hidden; background:#fff; }
    .permission-group-head { padding:11px 13px; background:#f4f7fb; border-bottom:1px solid var(--line); }
    .permission-group-toggle { display:flex; align-items:flex-start; gap:9px; cursor:pointer; font-weight:800; }
    .permission-children { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:0; }
    .permission-choice { padding:12px 13px; display:flex; gap:10px; align-items:flex-start; cursor:pointer; border-bottom:1px solid #edf1f5; }
    .permission-choice:hover { background:#f8fbff; }
    .permission-choice input { margin-top:3px; }
    .permission-choice .choice-sub strong { color:var(--ink); font-weight:600; }
    .menu-access-tree { display:grid; gap:12px; }
    .menu-access-group { border:1px solid var(--line); border-radius:9px; overflow:hidden; background:#fff; }
    .menu-access-items { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); }
    .menu-access-choice { padding:11px 13px; display:flex; gap:10px; align-items:flex-start; cursor:pointer; border-bottom:1px solid #edf1f5; }
    .menu-access-choice:hover { background:#f8fbff; }
    .user-submit-primary { display:flex !important; visibility:visible !important; opacity:1 !important; width:100%; justify-content:center; }
    .mobile-submit-bar { display:none; }
    @media (max-width:980px) {
        .access-layout { grid-template-columns:1fr; padding-bottom:78px; }
        .profile-panel { position:static; }
        .mobile-submit-bar { display:block; position:fixed; left:12px; right:12px; bottom:10px; z-index:90; padding:8px; border:1px solid var(--line); border-radius:10px; background:rgba(255,255,255,.96); box-shadow:0 8px 28px rgba(15,23,42,.2); backdrop-filter:blur(8px); }
        .mobile-submit-bar .btn { display:flex !important; visibility:visible !important; opacity:1 !important; width:100%; justify-content:center; }
        .permission-children { grid-template-columns:1fr; }
        .menu-access-items { grid-template-columns:1fr; }
    }
</style>

<div class="grid access-panels">
    <section class="card">
        <div class="section-head">
            <div>
                <h2>Roles</h2>
                <div class="access-count"><span data-count-for="role-check">0</span> selected</div>
            </div>
            <div class="mini-actions">
                <button class="mini-btn" type="button" data-select-all="role-check">Select All</button>
                <button class="mini-btn" type="button" data-clear-all="role-check">Clear</button>
            </div>
        </div>
        <div class="choice-grid">
            @foreach ($roles as $role)
                <label class="choice-card">
                    <input class="role-check" type="checkbox" name="roles[]" value="{{ $role->id }}"
                        data-role-permissions="{{ $role->permissions->pluck('id')->values()->toJson() }}"
                        @checked(in_array($role->id, $selectedRoles))>
                    <span>
                        <span class="choice-title">{{ $role->label }}</span>
                        <span class="choice-sub">{{ $role->name }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <div class="role-help">Selecting a role checks its permissions automatically. You can then turn any menu access off for this specific user.</div>
    </section>

    <section class="card">
        <div class="section-head">
            <div>
                <h2>Direct Permissions</h2>
                <div class="access-count"><span data-count-for="permission-check">0</span> menu permissions selected</div>
            </div>
            <div class="mini-actions">
                <button class="mini-btn" type="button" data-select-all="permission-check">Select All</button>
                <button class="mini-btn" type="button" data-clear-all="permission-check">Clear</button>
            </div>
        </div>
        <p class="muted">Menu &amp; submenu access is exact for this user. An unchecked item stays blocked even when a selected role normally grants it.</p>
        <input class="access-search" id="permissionSearch" type="search" placeholder="Search menu or permission" autocomplete="off">

        <div class="permission-tree" id="permissionTree">
            @foreach ($permissionGroups as $groupKey => $group)
                @php
                    $availableItems = collect($group['items'] ?? [])->filter(fn ($item, $name) => $permissionsByName->has($name));
                @endphp
                @if ($availableItems->isNotEmpty())
                    <section class="permission-menu-group" data-permission-group>
                        <div class="permission-group-head">
                            <label class="permission-group-toggle">
                                <input type="checkbox" data-permission-group-toggle="{{ $groupKey }}">
                                <span>{{ $group['label'] }}</span>
                            </label>
                        </div>
                        <div class="permission-children">
                            @foreach ($availableItems as $permissionName => $item)
                                @php
                                    $permission = $permissionsByName->get($permissionName);
                                    $menuNames = implode(', ', $item['menus'] ?? []);
                                    $searchText = strtolower($group['label'].' '.$item['label'].' '.$menuNames.' '.$permission->label.' '.$permission->name);
                                @endphp
                                <label class="permission-choice" data-permission-text="{{ $searchText }}">
                                    <input class="permission-check" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-group="{{ $groupKey }}" @checked(in_array($permission->id, $selectedPermissions))>
                                    <span>
                                        <span class="choice-title">{{ $item['label'] }}</span>
                                        <span class="choice-sub"><strong>Menus:</strong> {{ $menuNames }}</span>
                                        <span class="choice-sub">{{ $permission->label }} &middot; {{ $permission->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach

            @if ($otherPermissions->isNotEmpty())
                <section class="permission-menu-group" data-permission-group>
                    <div class="permission-group-head">
                        <label class="permission-group-toggle">
                            <input type="checkbox" data-permission-group-toggle="other">
                            <span>Other Access</span>
                        </label>
                    </div>
                    <div class="permission-children">
                        @foreach ($otherPermissions as $permission)
                            <label class="permission-choice" data-permission-text="{{ strtolower('other '.$permission->label.' '.$permission->name) }}">
                                <input class="permission-check" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-group="other" @checked(in_array($permission->id, $selectedPermissions))>
                                <span>
                                    <span class="choice-title">{{ $permission->label }}</span>
                                    <span class="choice-sub">{{ $permission->name }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="section-head">
            <div>
                <h2>Menu &amp; Submenu Access</h2>
                <div class="access-count"><span data-count-for="menu-check">0</span> menu items selected</div>
            </div>
            <div class="mini-actions">
                <button class="mini-btn" type="button" data-select-all="menu-check">Select All</button>
                <button class="mini-btn" type="button" data-clear-all="menu-check">Clear</button>
            </div>
        </div>
        <p class="muted">Tick the exact navigation items this user may open. Group checkboxes select or clear every submenu in that group.</p>
        <div class="menu-access-tree">
            @foreach ($menuGroups as $menuGroupKey => $menuGroup)
                <section class="menu-access-group">
                    <div class="permission-group-head">
                        <label class="permission-group-toggle">
                            <input type="checkbox" data-menu-group-toggle="{{ $menuGroupKey }}">
                            <span>{{ $menuGroup['label'] }}</span>
                        </label>
                    </div>
                    <div class="menu-access-items">
                        @foreach ($menuGroup['items'] as $menuKey => $menuItem)
                            @php $requiredPermission = $permissionsByName->get($menuItem['permission']); @endphp
                            @if ($requiredPermission)
                                <label class="menu-access-choice">
                                    <input class="menu-check" type="checkbox" name="menus[]" value="{{ $menuKey }}"
                                        data-menu-group="{{ $menuGroupKey }}" data-required-permission="{{ $requiredPermission->id }}"
                                        @checked(in_array($menuKey, $selectedMenus, true))>
                                    <span>
                                        <span class="choice-title">{{ $menuItem['label'] }}</span>
                                        <span class="choice-sub">{{ $requiredPermission->label }}</span>
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </section>
</div>

<div class="mobile-submit-bar" data-mobile-submit>
    <button class="btn" type="submit">{{ $submitLabel }}</button>
</div>

<script>
(() => {
    const permissionInputs = () => [...document.querySelectorAll('.permission-check')];

    function updateCount(className) {
        const count = document.querySelectorAll(`.${className}:checked`).length;
        document.querySelectorAll(`[data-count-for="${className}"]`).forEach(item => item.textContent = count);
    }

    const menuInputs = () => [...document.querySelectorAll('.menu-check')];

    function updatePermissionGroupStates() {
        document.querySelectorAll('[data-permission-group-toggle]').forEach(toggle => {
            const inputs = permissionInputs().filter(input => input.dataset.group === toggle.dataset.permissionGroupToggle);
            const checked = inputs.filter(input => input.checked).length;
            toggle.checked = inputs.length > 0 && checked === inputs.length;
            toggle.indeterminate = checked > 0 && checked < inputs.length;
        });
    }

    function updateMenuGroupStates() {
        document.querySelectorAll('[data-menu-group-toggle]').forEach(toggle => {
            const inputs = menuInputs().filter(input => input.dataset.menuGroup === toggle.dataset.menuGroupToggle);
            const checked = inputs.filter(input => input.checked).length;
            toggle.checked = inputs.length > 0 && checked === inputs.length;
            toggle.indeterminate = checked > 0 && checked < inputs.length;
        });
    }

    function refreshPermissions() {
        updateCount('permission-check');
        updatePermissionGroupStates();
    }

    function refreshMenus() {
        updateCount('menu-check');
        updateMenuGroupStates();
    }

    function enableRequiredPermission(menuInput) {
        if (!menuInput.checked) return;
        const permission = document.querySelector(`.permission-check[value="${menuInput.dataset.requiredPermission}"]`);
        if (permission) permission.checked = true;
        refreshPermissions();
    }

    function enableRolePermissions(roleInput) {
        if (!roleInput.checked) return;
        let permissionIds = [];
        try { permissionIds = JSON.parse(roleInput.dataset.rolePermissions || '[]'); } catch (error) { permissionIds = []; }
        permissionIds.forEach(id => {
            const permission = document.querySelector(`.permission-check[value="${id}"]`);
            if (permission) permission.checked = true;
            menuInputs()
                .filter(menu => menu.dataset.requiredPermission === String(id))
                .forEach(menu => menu.checked = true);
        });
        refreshPermissions();
        refreshMenus();
    }

    document.querySelectorAll('[data-select-all]').forEach(button => {
        button.addEventListener('click', () => {
            const className = button.dataset.selectAll;
            document.querySelectorAll(`.${className}`).forEach(input => {
                input.checked = true;
                if (className === 'role-check') enableRolePermissions(input);
            });
            updateCount(className);
            refreshPermissions();
            if (className === 'menu-check') {
                menuInputs().forEach(enableRequiredPermission);
                refreshMenus();
            }
        });
    });

    document.querySelectorAll('[data-clear-all]').forEach(button => {
        button.addEventListener('click', () => {
            const className = button.dataset.clearAll;
            document.querySelectorAll(`.${className}`).forEach(input => input.checked = false);
            updateCount(className);
            refreshPermissions();
            refreshMenus();
        });
    });

    document.querySelectorAll('.role-check').forEach(input => {
        input.addEventListener('change', () => {
            enableRolePermissions(input);
            updateCount('role-check');
        });
    });

    permissionInputs().forEach(input => input.addEventListener('change', () => {
        if (!input.checked) {
            menuInputs()
                .filter(menu => menu.dataset.requiredPermission === input.value)
                .forEach(menu => menu.checked = false);
        }
        refreshPermissions();
        refreshMenus();
    }));

    document.querySelectorAll('[data-permission-group-toggle]').forEach(toggle => {
        toggle.addEventListener('change', () => {
            permissionInputs()
                .filter(input => input.dataset.group === toggle.dataset.permissionGroupToggle)
                .forEach(input => input.checked = toggle.checked);
            if (!toggle.checked) {
                menuInputs().forEach(menu => {
                    const permission = document.querySelector(`.permission-check[value="${menu.dataset.requiredPermission}"]`);
                    if (permission && !permission.checked) menu.checked = false;
                });
            }
            refreshPermissions();
            refreshMenus();
        });
    });

    document.querySelectorAll('[data-menu-group-toggle]').forEach(toggle => {
        toggle.addEventListener('change', () => {
            menuInputs()
                .filter(input => input.dataset.menuGroup === toggle.dataset.menuGroupToggle)
                .forEach(input => {
                    input.checked = toggle.checked;
                    enableRequiredPermission(input);
                });
            refreshMenus();
        });
    });

    menuInputs().forEach(input => input.addEventListener('change', () => {
        enableRequiredPermission(input);
        refreshMenus();
    }));

    document.getElementById('permissionSearch')?.addEventListener('input', event => {
        const query = event.target.value.trim().toLowerCase();
        document.querySelectorAll('[data-permission-group]').forEach(group => {
            let visible = 0;
            group.querySelectorAll('[data-permission-text]').forEach(item => {
                const matches = item.dataset.permissionText.includes(query);
                item.style.display = matches ? 'flex' : 'none';
                if (matches) visible++;
            });
            group.style.display = visible ? 'block' : 'none';
        });
    });

    updateCount('role-check');
    refreshPermissions();
    refreshMenus();
})();
</script>
