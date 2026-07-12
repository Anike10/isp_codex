<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentAccountController extends Controller
{
    public function index(Request $request)
    {
        $allAccounts = PaymentAccount::query()
            ->withSum('payments as collected_amount', 'amount')
            ->withSum('expenses as spent_amount', 'amount')
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->get();

        $accounts = PaymentAccount::query()
            ->withSum('payments as collected_amount', 'amount')
            ->withSum('expenses as spent_amount', 'amount')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('account_name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->query('payment_method')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        $cashCollected = Payment::where('payment_method', 'cash')->sum('amount');
        $cashSpent = Expense::where('payment_method', 'cash')->sum('amount');
        $methodTotals = Payment::query()
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return view('payment_accounts.index', compact('accounts', 'allAccounts', 'cashCollected', 'cashSpent', 'methodTotals'));
    }

    public function create()
    {
        return view('payment_accounts.create');
    }

    public function show(Request $request, PaymentAccount $paymentAccount)
    {
        $paymentQuery = $this->filteredPaymentQuery(
            $paymentAccount->payments()->with(['customer', 'invoice']),
            $request
        );
        $expenseQuery = $this->filteredExpenseQuery(
            $paymentAccount->expenses()->with('employee'),
            $request
        );

        $perPage = $this->perPage($request);
        $filteredCollected = (float) (clone $paymentQuery)->sum('amount');
        $filteredSpent = (float) (clone $expenseQuery)->sum('amount');
        $filteredTransactions = (clone $paymentQuery)->count() + (clone $expenseQuery)->count();
        [$ledgerRows, $runningBalance] = $this->paginatedLedgerRows(
            $paymentQuery,
            $expenseQuery,
            $request,
            $perPage,
            (float) $paymentAccount->opening_balance,
            $this->priorCollectionTotal($paymentAccount->payments(), $request) - $this->priorExpenseTotal($paymentAccount->expenses(), $request)
        );

        $totalCollected = $paymentAccount->payments()->sum('amount');
        $totalSpent = $paymentAccount->expenses()->sum('amount');

        return view('payment_accounts.show', compact('paymentAccount', 'ledgerRows', 'totalCollected', 'totalSpent', 'filteredCollected', 'filteredSpent', 'filteredTransactions', 'runningBalance'));
    }

    public function cashLedger(Request $request)
    {
        $paymentQuery = $this->filteredPaymentQuery(
            Payment::query()
                ->with(['customer', 'invoice'])
                ->where('payment_method', 'cash'),
            $request
        );
        $expenseQuery = $this->filteredExpenseQuery(
            Expense::query()
                ->with('employee')
                ->where('payment_method', 'cash'),
            $request
        );

        $perPage = $this->perPage($request);
        $filteredCollected = (float) (clone $paymentQuery)->sum('amount');
        $filteredSpent = (float) (clone $expenseQuery)->sum('amount');
        $filteredTransactions = (clone $paymentQuery)->count() + (clone $expenseQuery)->count();
        [$ledgerRows, $runningBalance] = $this->paginatedLedgerRows(
            $paymentQuery,
            $expenseQuery,
            $request,
            $perPage,
            0,
            $this->priorCollectionTotal(Payment::query()->where('payment_method', 'cash'), $request)
                - $this->priorExpenseTotal(Expense::query()->where('payment_method', 'cash'), $request)
        );

        $totalCollected = Payment::where('payment_method', 'cash')->sum('amount');
        $totalSpent = Expense::where('payment_method', 'cash')->sum('amount');

        return view('payment_accounts.cash_ledger', compact('ledgerRows', 'totalCollected', 'totalSpent', 'filteredCollected', 'filteredSpent', 'filteredTransactions', 'runningBalance'));
    }

    public function store(Request $request)
    {
        PaymentAccount::create($request->validate([
            'payment_method' => ['required', 'in:bkash,nagad,bank'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]));

        return redirect()->route('payment-accounts.index')->with('success', 'Payment account created successfully.');
    }

    private function filteredPaymentQuery($query, Request $request)
    {
        return $query
            ->when($request->filled('from'), fn ($query) => $query->whereDate('payment_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('payment_date', '<=', $request->input('to')))
            ->when($request->filled('min_amount'), fn ($query) => $query->where('amount', '>=', (float) $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($query) => $query->where('amount', '<=', (float) $request->input('max_amount')))
            ->when(trim((string) $request->input('search')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('note', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($query) => $query->where('invoice_no', 'like', "%{$search}%")
                            ->orWhere('billing_month', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('connection_id', 'like', "%{$search}%"));
                });
            });
    }

    private function filteredExpenseQuery($query, Request $request)
    {
        return $query
            ->when($request->filled('from'), fn ($query) => $query->whereDate('expense_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('expense_date', '<=', $request->input('to')))
            ->when($request->filled('min_amount'), fn ($query) => $query->where('amount', '>=', (float) $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($query) => $query->where('amount', '<=', (float) $request->input('max_amount')))
            ->when(trim((string) $request->input('search')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('note', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('employee_name', 'like', "%{$search}%")
                        ->orWhere('salary_month', 'like', "%{$search}%");
                });
            });
    }

    private function priorCollectionTotal($query, Request $request): float
    {
        if (! $request->filled('from')) {
            return 0;
        }

        return (float) $query->whereDate('payment_date', '<', $request->input('from'))->sum('amount');
    }

    private function priorExpenseTotal($query, Request $request): float
    {
        if (! $request->filled('from')) {
            return 0;
        }

        return (float) $query->whereDate('expense_date', '<', $request->input('from'))->sum('amount');
    }

    private function paginatedLedgerRows($paymentQuery, $expenseQuery, Request $request, int $perPage, float $openingBalance, float $priorDateBalance): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;
        $startingBalance = $openingBalance + $priorDateBalance;

        $rows = $this->ledgerRows($paymentQuery, $expenseQuery);
        $runningBalance = $startingBalance + (float) $rows
            ->take($offset)
            ->sum('signed_amount');

        $pageRows = $rows
            ->slice($offset, $perPage)
            ->values();

        $paginator = new LengthAwarePaginator(
            $pageRows,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return [$paginator, $runningBalance];
    }

    private function ledgerRows($paymentQuery, $expenseQuery): Collection
    {
        $payments = (clone $paymentQuery)->get()->map(fn (Payment $payment): array => [
            'type' => 'payment',
            'date' => $payment->payment_date,
            'sort_date' => $payment->payment_date?->format('Y-m-d') ?? '',
            'sort_id' => ($payment->id * 2),
            'invoice' => $payment->invoice,
            'party' => $payment->customer?->name ?? 'N/A',
            'note' => $payment->note ?? 'Payment received',
            'credit' => (float) $payment->amount,
            'debit' => 0.0,
            'signed_amount' => (float) $payment->amount,
        ]);

        $expenses = (clone $expenseQuery)->get()->map(fn (Expense $expense): array => [
            'type' => 'expense',
            'date' => $expense->expense_date,
            'sort_date' => $expense->expense_date?->format('Y-m-d') ?? '',
            'sort_id' => ($expense->id * 2) + 1,
            'invoice' => null,
            'party' => $expense->employee_name ?: (Expense::CATEGORIES[$expense->category] ?? ucfirst((string) $expense->category)),
            'note' => $expense->note ?: ($expense->reference ?: (Expense::CATEGORIES[$expense->category] ?? 'Expense')),
            'credit' => 0.0,
            'debit' => (float) $expense->amount,
            'signed_amount' => -1 * (float) $expense->amount,
        ]);

        return $payments
            ->merge($expenses)
            ->sortBy([
                ['sort_date', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values();
    }
}
