<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeController;
use App\Models\Employee;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class EmployeeSalaryBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_month_payment_is_not_counted_in_opening_balance(): void
    {
        $employee = Employee::create([
            'name' => 'Md Mithun',
            'join_date' => '2026-05-01',
            'current_salary' => 7000,
            'salary_effective_from' => '2026-05-12',
            'yearly_bonus_count' => 0,
            'bonus_percent' => 0,
            'status' => 'active',
        ]);

        $employee->salaryRevisions()->create([
            'old_salary' => 0,
            'new_salary' => 7000,
            'effective_from' => '2026-05-12',
            'reason' => 'Starting salary',
        ]);

        Expense::create([
            'expense_type' => 'salary',
            'category' => 'salary',
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'salary_month' => '2026-05',
            'amount' => 2500,
            'payment_method' => 'cash',
            'expense_date' => '2026-05-01',
        ]);

        Expense::create([
            'expense_type' => 'salary',
            'category' => 'salary',
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'salary_month' => '2026-05',
            'amount' => 5000,
            'payment_method' => 'cash',
            'expense_date' => '2026-05-11',
        ]);

        $balance = $this->salaryBalanceForMonth($employee, '2026-05');

        $this->assertSame(0.0, $balance['opening_balance']);
        $this->assertSame(7000.0, $balance['payable']);
        $this->assertSame(7500.0, $balance['paid']);
        $this->assertSame(-500.0, $balance['closing_balance']);
        $this->assertSame(500.0, $balance['advance']);
    }

    private function salaryBalanceForMonth(Employee $employee, string $month): array
    {
        $controller = app(EmployeeController::class);
        $method = new ReflectionMethod($controller, 'salaryBalanceForMonth');
        $method->setAccessible(true);

        return $method->invoke($controller, $employee, $month);
    }
}
