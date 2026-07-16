<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleAssignmentHistory;
use App\Models\VehicleMaintenanceItem;
use App\Models\VehicleMaintenanceLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FleetService
{
    public function assignEmployee(Vehicle $vehicle, Employee $employee, array $data, ?int $userId): VehicleAssignmentHistory
    {
        return DB::transaction(function () use ($vehicle, $employee, $data, $userId): VehicleAssignmentHistory {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $activeAssignments = VehicleAssignmentHistory::query()
                ->whereNull('end_date')
                ->where(function ($query) use ($vehicle, $employee, $data): void {
                    $query->where(fn ($query) => $query->where('vehicle_id', $vehicle->id)->where('duty_role', $data['duty_role']))
                        ->orWhere('employee_id', $employee->id);
                })
                ->lockForUpdate()
                ->get();

            if ($activeAssignments->contains(fn ($assignment) => $assignment->vehicle_id === $vehicle->id && $assignment->employee_id === $employee->id && $assignment->duty_role === $data['duty_role'])) {
                throw new InvalidArgumentException($employee->name.' is already the active '.VehicleAssignmentHistory::ROLES[$data['duty_role']].' for this vehicle.');
            }

            foreach ($activeAssignments as $assignment) {
                if ($startDate->lt($assignment->start_date)) {
                    throw new InvalidArgumentException('New duty start date cannot be before the current assignment start date.');
                }

                $assignment->update(['end_date' => $startDate->equalTo($assignment->start_date) ? $startDate : $startDate->copy()->subDay()]);
            }

            $employee->update(['fleet_role' => $data['duty_role']]);

            return VehicleAssignmentHistory::create([
                ...$data,
                'vehicle_id' => $vehicle->id,
                'employee_id' => $employee->id,
                'assigned_by' => $userId,
            ]);
        });
    }

    public function endAssignment(VehicleAssignmentHistory $assignment, string $endDate): void
    {
        $date = Carbon::parse($endDate);
        if ($date->lt($assignment->start_date)) {
            throw new InvalidArgumentException('End date cannot be before the duty start date.');
        }

        $assignment->update(['end_date' => $date]);
    }

    public function logMaintenance(Vehicle $vehicle, ?VehicleMaintenanceItem $item, array $data, ?int $userId): VehicleMaintenanceLog
    {
        if ($item && $item->vehicle_id !== $vehicle->id) {
            throw new InvalidArgumentException('The maintenance item does not belong to this vehicle.');
        }

        return DB::transaction(function () use ($vehicle, $item, $data, $userId): VehicleMaintenanceLog {
            $log = VehicleMaintenanceLog::create([...$data, 'vehicle_id' => $vehicle->id, 'maintenance_item_id' => $item?->id, 'created_by' => $userId]);
            $serviceDate = Carbon::parse($data['service_date']);
            $mileage = isset($data['mileage']) ? (int) $data['mileage'] : null;
            if ($item) {
                $updates = [
                    'last_service_mileage' => $mileage,
                    'next_due_date' => $item->interval_days ? $serviceDate->copy()->addDays($item->interval_days) : null,
                    'next_due_mileage' => $item->interval_mileage && $mileage !== null ? $mileage + $item->interval_mileage : null,
                ];

                if ($data['action'] === 'checked') {
                    $updates['last_checked_at'] = $serviceDate;
                } else {
                    $updates['last_changed_at'] = $serviceDate;
                    $updates['last_checked_at'] = $serviceDate;
                }

                $item->update($updates);
            }
            if ($mileage !== null && $mileage > $vehicle->current_mileage) {
                $vehicle->update(['current_mileage' => $mileage]);
            }

            return $log;
        });
    }

    public function updateMaintenance(VehicleMaintenanceLog $log, ?VehicleMaintenanceItem $item, array $data): VehicleMaintenanceLog
    {
        if ($item && $item->vehicle_id !== $log->vehicle_id) {
            throw new InvalidArgumentException('The maintenance item does not belong to this vehicle.');
        }

        return DB::transaction(function () use ($log, $item, $data): VehicleMaintenanceLog {
            $oldItemId = $log->maintenance_item_id;
            $log->update([...$data, 'maintenance_item_id' => $item?->id]);

            foreach (array_unique(array_filter([$oldItemId, $item?->id])) as $itemId) {
                $this->recalculateMaintenanceItem(VehicleMaintenanceItem::findOrFail($itemId));
            }

            $mileage = isset($data['mileage']) ? (int) $data['mileage'] : null;
            if ($mileage !== null && $mileage > $log->vehicle->current_mileage) {
                $log->vehicle->update(['current_mileage' => $mileage]);
            }

            return $log->refresh();
        });
    }

    private function recalculateMaintenanceItem(VehicleMaintenanceItem $item): void
    {
        $latest = $item->logs()->latest('service_date')->latest('id')->first();
        $latestChange = $item->logs()->where('action', '!=', 'checked')->latest('service_date')->latest('id')->first();

        $item->update([
            'last_checked_at' => $latest?->service_date,
            'last_changed_at' => $latestChange?->service_date,
            'last_service_mileage' => $latest?->mileage,
            'next_due_date' => $latest && $item->interval_days ? $latest->service_date->copy()->addDays($item->interval_days) : null,
            'next_due_mileage' => $latest && $item->interval_mileage && $latest->mileage !== null ? $latest->mileage + $item->interval_mileage : null,
        ]);
    }
}
