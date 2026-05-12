<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const TYPES = [
        'salary' => 'Salary',
        'other' => 'Other Expense',
    ];

    public const CATEGORIES = [
        'salary' => 'Employee Salary',
        'bonus' => 'Bonus',
        'rent' => 'Office Rent',
        'utility' => 'Utility Bill',
        'internet' => 'Internet/Bandwidth',
        'transport' => 'Transport',
        'maintenance' => 'Maintenance',
        'equipment' => 'Equipment',
        'office' => 'Office Expense',
        'other' => 'Other',
    ];

    protected $fillable = [
        'entry_by',
        'entry_by_type',
        'expense_type',
        'category',
        'employee_name',
        'employee_designation',
        'salary_month',
        'amount',
        'payment_method',
        'payment_account_id',
        'expense_date',
        'reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PaymentAccount::class, 'payment_account_id');
    }
}
