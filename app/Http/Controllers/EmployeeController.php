<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $salaryMonth = $request->query('salary_month', now()->format('Y-m'));
        $bonusYear = (int) $request->query('bonus_year', now()->year);

        $employees = Employee::query()
            ->withSum([
                'expenses as selected_month_salary_paid' => fn ($query) => $query
                    ->where('expense_type', 'salary')
                    ->where('salary_month', $salaryMonth),
            ], 'amount')
            ->withSum([
                'expenses as selected_year_bonus_paid' => fn ($query) => $query
                    ->where('category', 'bonus')
                    ->whereYear('expense_date', $bonusYear),
            ], 'amount')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%'.$request->query('q').'%')
                        ->orWhere('designation', 'like', '%'.$request->query('q').'%')
                        ->orWhere('phone', 'like', '%'.$request->query('q').'%');
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        $employees->getCollection()->transform(function (Employee $employee) use ($salaryMonth) {
            $balance = $this->salaryBalanceForMonth($employee, $salaryMonth);
            $employee->selected_month_opening_balance = $balance['opening_balance'];
            $employee->selected_month_salary_paid = $balance['paid'];
            $employee->selected_month_salary_due = $balance['due'];
            $employee->selected_month_salary_advance = $balance['advance'];

            return $employee;
        });

        $activeEmployees = Employee::where('status', 'active')->get();
        $totalMonthlySalary = $activeEmployees->sum('current_salary');
        $salaryBalances = $activeEmployees->map(fn (Employee $employee) => $this->salaryBalanceForMonth($employee, $salaryMonth));
        $paidThisMonth = $salaryBalances->sum('paid');
        $salaryDueThisMonth = $salaryBalances->sum('due');
        $salaryAdvanceThisMonth = $salaryBalances->sum('advance');
        $yearlyBonusEntitlement = $activeEmployees->sum(fn (Employee $employee) => $this->yearlyBonusEntitlement($employee));
        $paidBonusThisYear = $activeEmployees->sum(fn (Employee $employee) => $employee->expenses()
            ->where('category', 'bonus')
            ->whereYear('expense_date', $bonusYear)
            ->sum('amount'));

        return view('employees.index', [
            'employees' => $employees,
            'salaryMonth' => $salaryMonth,
            'bonusYear' => $bonusYear,
            'totalMonthlySalary' => $totalMonthlySalary,
            'paidThisMonth' => $paidThisMonth,
            'salaryDueThisMonth' => $salaryDueThisMonth,
            'salaryAdvanceThisMonth' => $salaryAdvanceThisMonth,
            'yearlyBonusEntitlement' => $yearlyBonusEntitlement,
            'paidBonusThisYear' => $paidBonusThisYear,
            'bonusDueThisYear' => max(0, $yearlyBonusEntitlement - $paidBonusThisYear),
            'activeEmployees' => $activeEmployees->count(),
        ]);
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'current_salary' => ['required', 'numeric', 'min:0'],
            'salary_effective_from' => ['nullable', 'date'],
            'yearly_bonus_count' => ['required', 'integer', 'min:0', 'max:12'],
            'bonus_percent' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'status' => ['required', 'in:active,inactive'],
            'note' => ['nullable', 'string'],
        ]);

        $employee = Employee::create($data);

        if ((float) $data['current_salary'] > 0) {
            $employee->salaryRevisions()->create([
                'old_salary' => 0,
                'new_salary' => $data['current_salary'],
                'effective_from' => $data['salary_effective_from'] ?: now()->toDateString(),
                'reason' => 'Starting salary',
            ]);
        }

        return redirect()->route('employees.show', $employee)->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        $salaryMonth = request('salary_month', now()->format('Y-m'));
        $bonusYear = (int) request('bonus_year', now()->year);
        $salaryBalance = $this->salaryBalanceForMonth($employee, $salaryMonth);
        $monthlySalaryPaid = $salaryBalance['paid'];
        $yearlyBonusEntitlement = $this->yearlyBonusEntitlement($employee);
        $bonusPaid = (float) $employee->expenses()
            ->where('category', 'bonus')
            ->whereYear('expense_date', $bonusYear)
            ->sum('amount');
        $singleBonusAmount = $this->singleBonusAmount($employee);

        $employee->load([
            'salaryRevisions' => fn ($query) => $query->latest('effective_from')->latest(),
            'expenses' => fn ($query) => $query->latest('expense_date')->limit(20),
            'assetAssignments' => fn ($query) => $query->with(['product', 'returns'])->latest('assigned_at')->latest(),
        ]);

        return view('employees.show', [
            'employee' => $employee,
            'paidSalaryTotal' => $employee->expenses()->where('expense_type', 'salary')->sum('amount'),
            'salaryMonth' => $salaryMonth,
            'bonusYear' => $bonusYear,
            'monthlyOpeningBalance' => $salaryBalance['opening_balance'],
            'monthlyPayableSalary' => $salaryBalance['payable'],
            'monthlySalaryPaid' => $monthlySalaryPaid,
            'monthlySalaryDue' => $salaryBalance['due'],
            'monthlySalaryAdvance' => $salaryBalance['advance'],
            'monthlyClosingBalance' => $salaryBalance['closing_balance'],
            'singleBonusAmount' => $singleBonusAmount,
            'yearlyBonusEntitlement' => $yearlyBonusEntitlement,
            'bonusPaid' => $bonusPaid,
            'bonusDue' => max(0, $yearlyBonusEntitlement - $bonusPaid),
            'bonusPaidCount' => $singleBonusAmount > 0 ? min((int) $employee->yearly_bonus_count, (int) floor($bonusPaid / $singleBonusAmount)) : 0,
        ]);
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function balanceSheet(Request $request, Employee $employee)
    {
        $year = (int) $request->query('year', now()->year);
        $year = max(2000, min(2100, $year));
        $revisions = $employee->salaryRevisions()->orderBy('effective_from')->get();
        $salaryPayments = $employee->expenses()
            ->where('expense_type', 'salary')
            ->whereBetween('salary_month', [$year.'-01', $year.'-12'])
            ->get()
            ->groupBy('salary_month');
        $bonusPayments = $employee->expenses()
            ->where('category', 'bonus')
            ->whereYear('expense_date', $year)
            ->orderBy('expense_date')
            ->get();

        $runningBalance = $this->salaryBalanceBeforeMonth($employee, Carbon::create($year, 1, 1)->startOfDay());

        $rows = collect(range(1, 12))->map(function (int $month) use ($year, $employee, $revisions, $salaryPayments, &$runningBalance) {
            $monthDate = Carbon::create($year, $month, 1)->startOfDay();
            $salary = $this->salaryForMonth($employee, $revisions, $monthDate);
            $paid = (float) ($salaryPayments->get($monthDate->format('Y-m'))?->sum('amount') ?? 0);
            $openingBalance = $runningBalance;
            $runningBalance = round($runningBalance + $salary - $paid, 2);

            return [
                'month' => $monthDate->format('Y-m'),
                'month_name' => $monthDate->format('F Y'),
                'opening_balance' => $openingBalance,
                'payable' => $salary,
                'paid' => $paid,
                'closing_balance' => $runningBalance,
                'due' => max(0, $runningBalance),
                'advance' => max(0, -$runningBalance),
                'status' => $salary <= 0 ? 'not_applicable' : ($runningBalance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'due')),
            ];
        });

        $singleBonusAmount = $this->singleBonusAmount($employee);
        $yearlyBonusEntitlement = $this->yearlyBonusEntitlement($employee);
        $bonusPaid = (float) $bonusPayments->sum('amount');
        $bonusDue = max(0, $yearlyBonusEntitlement - $bonusPaid);
        $salaryPayable = $rows->sum('payable');
        $salaryPaid = $rows->sum('paid');
        $closingSalaryBalance = (float) $rows->last()['closing_balance'];
        $salaryDue = max(0, $closingSalaryBalance);
        $salaryAdvance = max(0, -$closingSalaryBalance);

        return view('employees.balance_sheet', [
            'employee' => $employee,
            'year' => $year,
            'rows' => $rows,
            'bonusPayments' => $bonusPayments,
            'salaryPayable' => $salaryPayable,
            'salaryPaid' => $salaryPaid,
            'salaryDue' => $salaryDue,
            'salaryAdvance' => $salaryAdvance,
            'openingSalaryBalance' => $rows->first()['opening_balance'],
            'closingSalaryBalance' => $closingSalaryBalance,
            'singleBonusAmount' => $singleBonusAmount,
            'yearlyBonusEntitlement' => $yearlyBonusEntitlement,
            'bonusPaid' => $bonusPaid,
            'bonusDue' => $bonusDue,
            'netPayable' => $salaryPayable + $yearlyBonusEntitlement,
            'netPaid' => $salaryPaid + $bonusPaid,
            'netDue' => $salaryDue + $bonusDue,
            'netAdvance' => $salaryAdvance,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'yearly_bonus_count' => ['required', 'integer', 'min:0', 'max:12'],
            'bonus_percent' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'status' => ['required', 'in:active,inactive'],
            'note' => ['nullable', 'string'],
        ]);

        $employee->update($data);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function storeSalaryRevision(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'new_salary' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldSalary = (float) $employee->current_salary;

        $employee->salaryRevisions()->create([
            'old_salary' => $oldSalary,
            'new_salary' => $data['new_salary'],
            'effective_from' => $data['effective_from'],
            'reason' => $data['reason'] ?? null,
        ]);

        $employee->update([
            'current_salary' => $data['new_salary'],
            'salary_effective_from' => $data['effective_from'],
        ]);

        return redirect()->route('employees.show', $employee)->with('success', 'Salary revision added successfully.');
    }

    private function singleBonusAmount(Employee $employee): float
    {
        return round((float) $employee->current_salary * (float) $employee->bonus_percent / 100, 2);
    }

    private function yearlyBonusEntitlement(Employee $employee): float
    {
        return round($this->singleBonusAmount($employee) * (int) $employee->yearly_bonus_count, 2);
    }

    private function salaryBalanceForMonth(Employee $employee, string $salaryMonth): array
    {
        $monthDate = Carbon::createFromFormat('Y-m-d', $salaryMonth.'-01')->startOfDay();
        $revisions = $employee->salaryRevisions()->orderBy('effective_from')->get();
        $openingBalance = $this->salaryBalanceBeforeMonth($employee, $monthDate, $revisions);
        $payable = $this->salaryForMonth($employee, $revisions, $monthDate);
        $paid = (float) $employee->expenses()
            ->where('expense_type', 'salary')
            ->where('salary_month', $salaryMonth)
            ->sum('amount');
        $closingBalance = round($openingBalance + $payable - $paid, 2);

        return [
            'opening_balance' => $openingBalance,
            'payable' => $payable,
            'paid' => $paid,
            'closing_balance' => $closingBalance,
            'due' => max(0, $closingBalance),
            'advance' => max(0, -$closingBalance),
        ];
    }

    private function salaryBalanceBeforeMonth(Employee $employee, Carbon $monthDate, $revisions = null): float
    {
        $revisions ??= $employee->salaryRevisions()->orderBy('effective_from')->get();
        $startMonth = $this->salaryLedgerStartMonth($employee, $monthDate, $revisions);
        $balance = 0.0;

        while ($startMonth->lt($monthDate)) {
            $salaryMonth = $startMonth->format('Y-m');
            $payable = $this->salaryForMonth($employee, $revisions, $startMonth);
            $paid = (float) $employee->expenses()
                ->where('expense_type', 'salary')
                ->where('salary_month', $salaryMonth)
                ->sum('amount');
            $balance = round($balance + $payable - $paid, 2);
            $startMonth->addMonth();
        }

        return $balance;
    }

    private function salaryLedgerStartMonth(Employee $employee, Carbon $targetMonth, $revisions): Carbon
    {
        $candidates = collect([
            $employee->join_date,
            $employee->salary_effective_from,
            $revisions->min('effective_from'),
        ])->filter();

        $firstPaymentMonth = $employee->expenses()
            ->where('expense_type', 'salary')
            ->whereNotNull('salary_month')
            ->min('salary_month');

        if ($firstPaymentMonth) {
            $candidates->push(Carbon::createFromFormat('Y-m-d', $firstPaymentMonth.'-01'));
        }

        $start = $candidates->min() ?: $targetMonth->copy()->startOfYear();

        return Carbon::parse($start)->startOfMonth();
    }

    private function salaryForMonth(Employee $employee, $revisions, Carbon $monthDate): float
    {
        $monthEnd = $monthDate->copy()->endOfMonth();
        $revision = $revisions
            ->filter(fn ($revision) => $revision->effective_from->lte($monthEnd))
            ->sortByDesc('effective_from')
            ->first();

        if ($revision) {
            return (float) $revision->new_salary;
        }

        if ($employee->salary_effective_from && $employee->salary_effective_from->gt($monthEnd)) {
            return 0;
        }

        return (float) $employee->current_salary;
    }
}
