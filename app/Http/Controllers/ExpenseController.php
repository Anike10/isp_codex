<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Employee;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\PaymentAccountPreferenceService;
use App\Services\PrintContextService;

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
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_designation', 'like', "%{$search}%")
                        ->orWhere('salary_month', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('account', fn ($query) => $query
                            ->where('account_name', 'like', "%{$search}%")
                            ->orWhere('account_number', 'like', "%{$search}%"));
                });
            })
            ->when($from, fn ($query) => $query->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('expense_date', '<=', $to));

        $summaryExpenses = (clone $baseQuery)->get();
        $salaryTotal = $summaryExpenses->where('expense_type', 'salary')->sum('amount');
        $otherTotal = $summaryExpenses->where('expense_type', 'other')->sum('amount');
        $grandTotal = $salaryTotal + $otherTotal;
        $employeeCount = $summaryExpenses->where('expense_type', 'salary')->pluck('employee_name')->filter()->unique()->count();

        $expenses = $baseQuery
            ->with(['account', 'employee'])
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

    public function create(Request $request, PaymentAccountPreferenceService $preferenceService)
    {
        return view('expenses.create', [
            'types' => Expense::TYPES,
            'categories' => Expense::CATEGORIES,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
            'paymentAccounts' => PaymentAccount::where('status', 'active')->usableBy($request->user())->orderBy('payment_method')->orderBy('account_name')->get(),
            'paymentDefault' => $preferenceService->forUser($request->user()),
        ]);
    }

    public function store(Request $request, PaymentAccountPreferenceService $preferenceService)
    {
        $data = $request->validate([
            'expense_type' => ['required', Rule::in(array_keys(Expense::TYPES))],
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'employee_name' => ['nullable', 'required_if:expense_type,salary', 'string', 'max:255'],
            'employee_designation' => ['nullable', 'string', 'max:255'],
            'salary_month' => ['nullable', 'required_if:expense_type,salary', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable'],
            'expense_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);
        $rememberAsDefault = $request->boolean('set_as_default');
        unset($data['set_as_default']);

        if ($data['payment_method'] === 'cash') {
            $data['payment_account_id'] = null;
        } else {
            $account = PaymentAccount::where('id', $data['payment_account_id'] ?? null)
                ->where('payment_method', $data['payment_method'])
                ->where('status', 'active')
                ->usableBy($request->user())
                ->first();

            if (! $account) {
                return back()->withInput()->withErrors(['payment_account_id' => 'Please select a valid account for this payment method.']);
            }

            $data['payment_account_id'] = $account->id;
        }

        $isEmployeeCost = $data['expense_type'] === 'salary' || $data['category'] === 'bonus';

        if (! $isEmployeeCost) {
            $data['employee_id'] = null;
            $data['employee_name'] = null;
            $data['employee_designation'] = null;
            $data['salary_month'] = null;
        } else {
            if ($data['expense_type'] === 'salary') {
                $data['category'] = 'salary';
            } else {
                $data['salary_month'] = null;
            }

            if (! empty($data['employee_id'])) {
                $employee = Employee::find($data['employee_id']);
                $data['employee_name'] = $employee->name;
                $data['employee_designation'] = $employee->designation;
            }
        }

        $data['entry_by'] = $request->user()?->name;
        $data['entry_by_type'] = 'user';

        Expense::create($data);

        $preferenceService->remember($request->user(), $rememberAsDefault, $data['payment_method'], $data['payment_account_id']);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function show(Expense $expense)
    {
        $expense->load(['account', 'employee']);

        return view('expenses.show', [
            'expense' => $expense,
            'types' => Expense::TYPES,
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function voucher(Request $request, Expense $expense, PrintContextService $printContext)
    {
        $expense->load(['account', 'employee']);
        $typeLabel = $expense->category === 'bonus'
            ? 'Bonus Payment'
            : (Expense::TYPES[$expense->expense_type] ?? ucfirst($expense->expense_type));
        $party = $expense->employee_name
            ?: (Expense::CATEGORIES[$expense->category] ?? ucfirst($expense->category));

        return view('accounting.voucher', array_merge([
            'voucher' => [
                'title' => 'Expense Voucher',
                'voucher_no' => 'EXP-'.$expense->id,
                'date' => $expense->expense_date,
                'type' => $typeLabel,
                'amount' => (float) $expense->amount,
                'paid_to_label' => $expense->expense_type === 'salary' || $expense->category === 'bonus' ? 'Paid To' : 'Expense Head',
                'paid_to' => $party,
                'secondary_label' => $expense->expense_type === 'salary' ? 'Salary Month' : 'Category',
                'secondary_value' => $expense->expense_type === 'salary' ? ($expense->salary_month ?: 'N/A') : (Expense::CATEGORIES[$expense->category] ?? ucfirst($expense->category)),
                'method' => ucfirst($expense->payment_method),
                'account' => $expense->account ? $expense->account->account_name.' - '.$expense->account->account_number : 'Cash',
                'reference' => $expense->reference ?: 'Expense #'.$expense->id,
                'note' => $expense->note ?: 'No note added.',
                'back_url' => route('expenses.show', $expense),
            ],
            'printable' => $expense, 'documentType' => 'expense_voucher',
        ], $printContext->for($request)));
    }
}
