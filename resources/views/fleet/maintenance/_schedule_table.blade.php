@include('partials.per_page')
<table>
    <thead><tr><th>SL</th><th>Vehicle</th><th>Maintenance Item</th><th>Type</th><th>Interval</th><th>Last Done</th><th>Next Due</th><th>Remaining</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    @forelse($schedules as $item)
        @php
            $status = $item->dueStatus($item->vehicle->current_mileage);
            $days = $item->daysRemaining();
            $km = $item->mileageRemaining($item->vehicle->current_mileage);
        @endphp
        <tr>
            <td>{{ $schedules->firstItem() + $loop->index }}</td>
            <td><a href="{{ route('fleet.show',$item->vehicle) }}"><strong>{{ $item->vehicle->registration_no }}</strong></a><div class="muted">{{ $item->vehicle->name }} | {{ number_format($item->vehicle->current_mileage) }} km</div></td>
            <td><strong>{{ $item->name }}</strong>@if($item->note)<div class="muted">{{ $item->note }}</div>@endif</td>
            <td>{{ \App\Models\VehicleMaintenanceItem::TYPES[$item->maintenance_type] }}</td>
            <td>{{ $item->interval_days ? $item->interval_days.' days' : 'No date interval' }}<div class="muted">{{ $item->interval_mileage ? number_format($item->interval_mileage).' km' : 'No mileage interval' }}</div></td>
            <td>{{ $item->last_changed_at?->format('Y-m-d') ?? $item->last_checked_at?->format('Y-m-d') ?? 'Never' }}<div class="muted">{{ $item->last_service_mileage ? number_format($item->last_service_mileage).' km' : 'Mileage N/A' }}</div></td>
            <td>{{ $item->next_due_date?->format('Y-m-d') ?? 'N/A' }}<div class="muted">{{ $item->next_due_mileage ? number_format($item->next_due_mileage).' km' : 'N/A' }}</div></td>
            <td>
                @if($days === null) Date N/A @elseif($days < 0)<strong>{{ abs($days) }} days overdue</strong>@elseif($days === 0)<strong>Due today</strong>@else {{ $days }} days left @endif
                <div class="muted">@if($km === null) Mileage N/A @elseif($km < 0){{ number_format(abs($km)) }} km overdue @elseif($km === 0)Due at current mileage @else{{ number_format($km) }} km left @endif</div>
            </td>
            <td><span class="badge {{ $status === 'overdue' ? 'overdue' : ($status === 'due' ? 'due' : ($status === 'upcoming' ? 'active' : 'inactive')) }}">{{ ucfirst($status) }}</span></td>
            <td><a class="btn light" href="{{ route('fleet.maintenance.logs.create',['vehicle_id'=>$item->vehicle_id,'maintenance_item_id'=>$item->id]) }}">Log Work</a></td>
        </tr>
    @empty<tr><td colspan="10">No maintenance schedule found.</td></tr>@endforelse
    </tbody>
</table>
<div style="margin-top:16px">{{ $schedules->links() }}</div>
