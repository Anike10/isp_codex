# Vehicle & Fleet Management

## Operator pages

- `/fleet`: vehicle dashboard, status/mileage filters, current Driver/Helper/Supervisor, due-service count, and a visible Add Vehicle button.
- `/fleet/create`: dedicated vehicle entry form for cars, pickups, trucks, motorcycles, vans, and other vehicle types.
- `/fleet/maintenance/schedules`: central periodic-maintenance entry and status page showing days/km remaining, due, overdue, upcoming, and unscheduled items across every vehicle.
- `/fleet/maintenance/logs/create`: central repair/check/change/service entry. A periodic item is optional, so one-off clutch/body/electrical repairs can be logged directly; scheduled work recalculates the next due date and mileage. Each work record can include up to 10 private expense/work photos and one YouTube video link.
- `/fleet/settings`: Fleet-specific settings, including the maximum allowed size per uploaded maintenance image (1-50 MB, default 5 MB).
- `/fleet/{vehicle}`: vehicle edit, maintenance schedules/logs, staff assignment and duty closing, itemized expenses, and recent history.
- `/fleet/maintenance/logs/{log}` and `/fleet/expenses/{expense}`: complete detail pages opened by clicking any recent/report table row.
- Maintenance and expense entries begin as editable drafts. Their detail pages provide `Edit Draft` and `Final & Lock`; finalization stores the responsible user/time and permanently blocks later edits.
- `/fleet/reports`: report selection hub.
- `/fleet/reports/expenses`: vehicle totals and itemized expense report with date, vehicle, employee, and category filters.
- `/fleet/reports/maintenance`: maintenance log report with date, vehicle, and action filters.
- `/fleet/reports/maintenance-due`: read-only periodic due/overdue report with date and mileage remaining.
- `/fleet/reports/duty-history`: staff duty history with overlap-aware date, vehicle, employee, role, and current/ended filters.
- Access is controlled by `manage_fleet`; menu visibility and direct routes both enforce the permission.

## Database schema

### `vehicles`

Vehicle master data: unique registration/chassis/engine identifiers, name/type/make/model/year/fuel, `active|maintenance|inactive` status, current mileage, and notes.

### `vehicle_maintenance_items`

One schedule per vehicle and item name. `maintenance_type` is `routine_check` or `replacement`. Date scheduling uses `interval_days`/`next_due_date`; mileage scheduling uses `interval_mileage`/`next_due_mileage`. The row also preserves last check/change date and last service mileage.

### `vehicle_maintenance_logs`

Append-only operational records for `checked`, `changed`, `serviced`, and `repaired` actions, including an optional schedule link, standalone `work_name`, date, mileage, cost, vendor, details, YouTube link, private photo attachments, and creator. Saving scheduled work recalculates the item's next date/mileage; every log advances vehicle mileage when appropriate. Photos use protected `manage_fleet` routes rather than public storage paths.

Maintenance logs and vehicle expenses use `finalized_at` / `finalized_by` for draft locking. Every draft update and finalization saves a `record_versions` snapshot, including maintenance photo metadata, editor/finalizer identity, changed fields, and the old values shown in `Edit History`.

### `vehicle_assignments_history`

The historical source of truth for vehicle staffing. Each row links vehicle and employee with `driver`, `helper`, or `supervisor`, plus start/end dates, note, and assigning user. There is no separate overwrite-only current-assignment table: current duty is the row with `end_date IS NULL`.

When a new person is assigned to a vehicle/role, the service transaction locks and closes the previous active vehicle/role row on the day before the new start date. It also closes that employee's other active duty regardless of role, preventing one employee from remaining active on two vehicles. Manual duty ending validates that the end is not before the start.

### `vehicle_expenses`

Itemized operating expenses linked to vehicle, optional responsible driver/employee, and creator user. Categories are Diesel, Octane, CNG, Engine Oil, Spare Parts, Toll/Bridge, and Miscellaneous. Date, amount, optional quantity/unit, mileage, trip reference, vendor, and description are retained.

### `employees.fleet_role`

Optional employee classification: `driver`, `helper`, or `supervisor`. It can be managed from Employee create/edit and is synchronized when a duty assignment is made.

## Backend structure

- Models: `Vehicle`, `VehicleMaintenanceItem`, `VehicleMaintenanceLog`, `VehicleAssignmentHistory`, `VehicleExpense`.
- `FleetService`: transactional assignment rollover, duty closing, and maintenance schedule recalculation.
- `FleetController`: vehicle dashboard/master data.
- `FleetOperationController`: schedule/log, assignment, and expense writes.
- `FleetReportController`: database aggregates and independently paginated report details.

All write actions validate foreign keys, allowed status/category/action values, dates, mileage, and monetary inputs. Assignment replacement and maintenance updates run in database transactions.

## Reporting rules

- Date range is inclusive.
- Expense totals are calculated in SQL from the filtered `vehicle_expenses` rows, not from the current page.
- Duty history uses overlap semantics: a duty is included when it was active at any point inside the requested date range.
- Detail sections use independent paginator names so navigating expense pages does not reset maintenance or duty-history paging.
