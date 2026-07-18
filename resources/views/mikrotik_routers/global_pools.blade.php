@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Global IP Pools</h1>
        <div class="muted">একই নামের pool একটি row-তে; linked সব MikroTik router একইসাথে দেখা ও পরিচালনা করা যাবে।</div>
    </div>
</div>

@include('partials.per_page')

<table>
    <thead>
        <tr>
            <th>Pool</th>
            <th>IP Ranges</th>
            <th>Routers</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pools as $pool)
            <tr data-pool-name="{{ $pool->name }}">
                <td><strong>{{ $pool->name }}</strong></td>
                <td>
                    @foreach ($pool->ranges as $range)
                        <div>{{ $range }}</div>
                    @endforeach
                    @if ($pool->ranges->count() > 1)
                        <div class="muted">Routerগুলোর range বর্তমানে এক নয়</div>
                    @endif
                </td>
                <td>
                    <div class="actions">
                        @foreach ($pool->entries as $entry)
                            @if ($entry->router)
                                <a class="btn light" href="{{ route('mikrotik-routers.pools.index', $entry->router) }}">
                                    {{ $entry->router->name }}
                                </a>
                            @else
                                <span class="badge inactive">No router</span>
                            @endif
                        @endforeach
                    </div>
                </td>
                <td>
                    <div class="actions">
                        <details>
                            <summary class="btn secondary">Edit / Copy</summary>
                            <form method="post" action="{{ route('ip-pools.update', $pool->representative) }}" class="card" style="margin-top:8px;min-width:340px">
                                @csrf
                                @method('PATCH')
                                <div style="margin-bottom:8px">
                                    <label>Pool name</label>
                                    <input name="name" value="{{ $pool->name }}" required>
                                </div>
                                <div style="margin-bottom:8px">
                                    <label>IP ranges</label>
                                    <input name="ranges" value="{{ $pool->ranges->first() }}" required>
                                </div>
                                <label style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
                                    <input type="checkbox" name="sync_to_routers" value="1" style="width:auto;margin-top:3px">
                                    <span>
                                        <strong>সব MikroTik-এও পরিবর্তন করুন</strong>
                                        <span class="muted" style="display:block">Checked: সব linked router ও App record update হবে। Unchecked: নাম বদলালে App-এ নতুন copy হবে; পুরোনো pool ও MikroTik অপরিবর্তিত থাকবে।</span>
                                    </span>
                                </label>
                                <button class="btn" type="submit">Apply</button>
                            </form>
                        </details>

                        <details>
                            <summary class="btn danger">Delete</summary>
                            <form method="post" action="{{ route('ip-pools.destroy', $pool->representative) }}" class="card" style="margin-top:8px;min-width:340px" onsubmit="return confirm('Delete pool {{ addslashes($pool->name) }} using the selected option?')">
                                @csrf
                                @method('DELETE')
                                <div style="margin-bottom:8px">App-এর একই নামের {{ $pool->entries->count() }}টি record delete হবে।</div>
                                <label style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px">
                                    <input type="checkbox" name="delete_from_routers" value="1" style="width:auto;margin-top:3px">
                                    <span>
                                        <strong>সব linked MikroTik থেকেও delete করুন</strong>
                                        <span class="muted" style="display:block">Unchecked থাকলে শুধু App থেকে delete হবে।</span>
                                    </span>
                                </label>
                                <button class="btn danger" type="submit">Confirm Delete</button>
                            </form>
                        </details>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">No App IP pools saved yet.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:16px">{{ $pools->links() }}</div>
@endsection
