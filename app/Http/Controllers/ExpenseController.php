<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $baseQuery = Expense::query()
            ->when($request->filled('expense_type'), fn ($query) => $query->where('expense_type', $request->query('expense_type')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->query('category')))
            ->when($request->filled('employee_name'), fn ($query) => $query->where('employee_name', 'like', '%'.$request->query('employee_name').'%'))
            ->when($from, fn ($query) => $query->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('expense_date', '<=', $to));

        $summaryExpenses = (clone $baseQuery)->get();
        $salaryTotal = $summaryExpenses->where('expense_type', 'salary')->sum('amount');
        $otherTotal = $summaryExpenses->where('expense_type', 'other')->sum('amount');
        $grandTotal = $salaryTotal + $otherTotal;
        $employeeCount = $summaryExpenses->where('expense_type', 'salary')->pluck('employee_name')->filter()->unique()->count();

        $expenses = $baseQuery
            ->with('account')
            ->latest('expense_date')
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('expenses.index', [
            'expenses' => $expenses,
            'salaryTotal' => $salaryTotal,
            'otherTotal' => $otherTotal,
            'grandTotal' => $grandTotal,
            'employeeCount' => $employeeCount,
            'types' => Expense::TYPES,
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function create()
    {
        return view('expenses.create', [
            'types' => Expense::TYPES,
            'categories' => Expense::CATEGORIES,
            'paymentAccounts' => PaymentAccount::where('status', 'active')->orderBy('payment_method')->orderBy('account_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_type' => ['required', Rule::in(array_keys(Expense::TYPES))],
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'employee_name' => ['nullable', 'required_if:expense_type,salary', 'string', 'max:255'],
            'employee_designation' => ['nullable', 'string', 'max:255'],
            'salary_month' => ['nullable', 'required_if:expense_type,salary', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable'],
            'expense_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] === 'cash') {
            $data['payment_account_id'] = null;
        } else {
            $account = PaymentAccount::where('id', $data['payment_account_id'] ?? null)
                ->where('payment_method', $data['payment_method'])
                ->where('status', 'active')
                ->first();

            if (! $account) {
                return back()->withInput()->withErrors(['payment_account_id' => 'Please select a valid account for this payment method.']);
            }

            $data['payment_account_id'] = $account->id;
        }

        if ($data['expense_type'] !== 'salary') {
            $data['employee_name'] = null;
            $data['employee_designation'] = null;
            $data['salary_month'] = null;
        } else {
            $data['category'] = 'salary';
        }

        $data['entry_by'] = $request->user()?->name;
        $data['entry_by_type'] = 'user';

        Expense::create($data);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function show(Expense $expense)
    {
        $expense->load('account');

        return view('expenses.show', [
            'expense' => $expense,
            'types' => Expense::TYPES,
            'categories' => Expense::CATEGORIES,
        ]);
    }
}
