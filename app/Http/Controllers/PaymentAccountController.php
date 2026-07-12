<?php

namespace App\Http\Controllers;

use App\Models\CustomerBalanceTransaction;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentAccountController extends Controller
{
    public function index(Request $request)
    {
        $allAccounts = PaymentAccount::query()
            ->withSum('payments as collected_amount', 'amount')
            ->withSum([
                'balanceTransactions as advance_collected_amount' => fn ($query) => $query
                    ->where('direction', 'credit')
                    ->whereNull('payment_id'),
            ], 'amount')
            ->withSum('expenses as spent_amount', 'amount')
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->get();

        $accounts = PaymentAccount::query()
            ->withSum('payments as collected_amount', 'amount')
            ->withSum([
                'balanceTransactions as advance_collected_amount' => fn ($query) => $query
                    ->where('direction', 'credit')
                    ->whereNull('payment_id'),
            ], 'amount')
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

        $cashCollected = Payment::where('payment_method', 'cash')->sum('amount')
            + CustomerBalanceTransaction::query()
                ->where('payment_method', 'cash')
                ->where('direction', 'credit')
                ->whereNull('payment_id')
                ->sum('amount');
        $cashSpent = Expense::where('payment_method', 'cash')->sum('amount');

        return view('payment_accounts.index', compact('accounts', 'allAccounts', 'cashCollected', 'cashSpent'));
    }

    public function create()
    {
        return view('payment_accounts.create');
    }

    public function show(Request $request, PaymentAccount $paymentAccount)
    {
        $paymentQuery = $this->filteredPaymentQuery(
            Payment::query()->where('payment_account_id', $paymentAccount->id),
            $request
        );
        $expenseQuery = $this->filteredExpenseQuery(
            Expense::query()->where('payment_account_id', $paymentAccount->id),
            $request
        );
        $advanceQuery = $this->filteredAdvanceQuery(
            CustomerBalanceTransaction::query()
                ->where('payment_account_id', $paymentAccount->id)
                ->where('direction', 'credit')
                ->whereNull('payment_id'),
            $request
        );

        $perPage = $this->perPage($request);
        $filteredCollected = (float) (clone $paymentQuery)->sum('amount')
            + (float) (clone $advanceQuery)->sum('amount');
        $filteredSpent = (float) (clone $expenseQuery)->sum('amount');
        [$ledgerRows, $runningBalance] = $this->paginatedLedgerRows(
            $paymentQuery,
            $expenseQuery,
            $advanceQuery,
            $request,
            $perPage,
            (float) $paymentAccount->opening_balance,
            $this->priorCollectionTotal($paymentAccount->payments(), $request)
                + $this->priorAdvanceTotal($paymentAccount->balanceTransactions()
                    ->where('direction', 'credit')
                    ->whereNull('payment_id'), $request)
                - $this->priorExpenseTotal($paymentAccount->expenses(), $request)
        );
        $filteredTransactions = $ledgerRows->total();

        $totalCollected = $paymentAccount->payments()->sum('amount')
            + $paymentAccount->balanceTransactions()
                ->where('direction', 'credit')
                ->whereNull('payment_id')
                ->sum('amount');
        $totalSpent = $paymentAccount->expenses()->sum('amount');

        return view('payment_accounts.show', compact('paymentAccount', 'ledgerRows', 'totalCollected', 'totalSpent', 'filteredCollected', 'filteredSpent', 'filteredTransactions', 'runningBalance'));
    }

    public function cashLedger(Request $request)
    {
        $paymentQuery = $this->filteredPaymentQuery(
            Payment::query()
                ->where('payment_method', 'cash'),
            $request
        );
        $expenseQuery = $this->filteredExpenseQuery(
            Expense::query()
                ->where('payment_method', 'cash'),
            $request
        );
        $advanceQuery = $this->filteredAdvanceQuery(
            CustomerBalanceTransaction::query()
                ->where('payment_method', 'cash')
                ->where('direction', 'credit')
                ->whereNull('payment_id'),
            $request
        );

        $perPage = $this->perPage($request);
        $filteredCollected = (float) (clone $paymentQuery)->sum('amount')
            + (float) (clone $advanceQuery)->sum('amount');
        $filteredSpent = (float) (clone $expenseQuery)->sum('amount');
        [$ledgerRows, $runningBalance] = $this->paginatedLedgerRows(
            $paymentQuery,
            $expenseQuery,
            $advanceQuery,
            $request,
            $perPage,
            0,
            $this->priorCollectionTotal(Payment::query()->where('payment_method', 'cash'), $request)
                + $this->priorAdvanceTotal(CustomerBalanceTransaction::query()
                    ->where('payment_method', 'cash')
                    ->where('direction', 'credit')
                    ->whereNull('payment_id'), $request)
                - $this->priorExpenseTotal(Expense::query()->where('payment_method', 'cash'), $request)
        );
        $filteredTransactions = $ledgerRows->total();

        $totalCollected = Payment::where('payment_method', 'cash')->sum('amount')
            + CustomerBalanceTransaction::query()
                ->where('payment_method', 'cash')
                ->where('direction', 'credit')
                ->whereNull('payment_id')
                ->sum('amount');
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
            ->when($request->filled('from'), fn ($query) => $query->where('payments.payment_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->where('payments.payment_date', '<=', $request->input('to')))
            ->when($request->filled('min_amount'), fn ($query) => $query->where('payments.amount', '>=', (float) $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($query) => $query->where('payments.amount', '<=', (float) $request->input('max_amount')))
            ->when(trim((string) $request->input('search')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('payments.note', 'like', "%{$search}%")
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
            ->when($request->filled('from'), fn ($query) => $query->where('expenses.expense_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->where('expenses.expense_date', '<=', $request->input('to')))
            ->when($request->filled('min_amount'), fn ($query) => $query->where('expenses.amount', '>=', (float) $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($query) => $query->where('expenses.amount', '<=', (float) $request->input('max_amount')))
            ->when(trim((string) $request->input('search')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('expenses.note', 'like', "%{$search}%")
                        ->orWhere('expenses.reference', 'like', "%{$search}%")
                        ->orWhere('expenses.category', 'like', "%{$search}%")
                        ->orWhere('expenses.employee_name', 'like', "%{$search}%")
                        ->orWhere('expenses.salary_month', 'like', "%{$search}%");
                });
            });
    }

    private function filteredAdvanceQuery($query, Request $request)
    {
        return $query
            ->when($request->filled('from'), fn ($query) => $query->where('customer_balance_transactions.transaction_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->where('customer_balance_transactions.transaction_date', '<=', $request->input('to')))
            ->when($request->filled('min_amount'), fn ($query) => $query->where('customer_balance_transactions.amount', '>=', (float) $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($query) => $query->where('customer_balance_transactions.amount', '<=', (float) $request->input('max_amount')))
            ->when(trim((string) $request->input('search')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('customer_balance_transactions.note', 'like', "%{$search}%")
                        ->orWhere('customer_balance_transactions.reference', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('connection_id', 'like', "%{$search}%"));
                });
            });
    }

    private function priorCollectionTotal($query, Request $request): float
    {
        if (! $request->filled('from')) {
            return 0;
        }

        return (float) $query->where('payments.payment_date', '<', $request->input('from'))->sum('amount');
    }

    private function priorExpenseTotal($query, Request $request): float
    {
        if (! $request->filled('from')) {
            return 0;
        }

        return (float) $query->where('expenses.expense_date', '<', $request->input('from'))->sum('amount');
    }

    private function priorAdvanceTotal($query, Request $request): float
    {
        if (! $request->filled('from')) {
            return 0;
        }

        return (float) $query
            ->where('customer_balance_transactions.transaction_date', '<', $request->input('from'))
            ->sum('amount');
    }

    private function paginatedLedgerRows($paymentQuery, $expenseQuery, $advanceQuery, Request $request, int $perPage, float $openingBalance, float $priorDateBalance): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;
        $startingBalance = $openingBalance + $priorDateBalance;

        $orderedRows = DB::query()
            ->fromSub($this->ledgerQuery($paymentQuery, $expenseQuery, $advanceQuery), 'ledger_rows')
            ->orderBy('ledger_date')
            ->orderBy('row_id')
            ->orderBy('sort_type');

        $total = (clone $orderedRows)->count();
        $priorPageBalance = 0.0;

        if ($offset > 0) {
            $priorRows = (clone $orderedRows)
                ->select('signed_amount')
                ->limit($offset);

            $priorPageBalance = (float) DB::query()
                ->fromSub($priorRows, 'prior_ledger_rows')
                ->sum('signed_amount');
        }

        $runningBalance = $startingBalance + $priorPageBalance;

        $pageRows = (clone $orderedRows)
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function (object $row): array {
                $type = (string) $row->row_type;
                $expenseLabel = Expense::CATEGORIES[$row->category] ?? ucfirst((string) $row->category);
                $defaultNote = match ($type) {
                    'payment' => 'Payment received',
                    'advance' => 'Advance payment',
                    default => $expenseLabel,
                };

                return [
                    'type' => $type,
                    'date' => $row->ledger_date ? Carbon::parse($row->ledger_date) : null,
                    'invoice_id' => $row->invoice_id ? (int) $row->invoice_id : null,
                    'invoice_no' => $row->invoice_no,
                    'party' => $type !== 'expense'
                        ? ($row->party ?: 'N/A')
                        : ($row->party ?: $expenseLabel),
                    'note' => $row->note ?: ($row->reference ?: $defaultNote),
                    'credit' => (float) $row->credit,
                    'debit' => (float) $row->debit,
                    'signed_amount' => (float) $row->signed_amount,
                ];
            });

        $paginator = new LengthAwarePaginator(
            $pageRows,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return [$paginator, $runningBalance];
    }

    private function ledgerQuery($paymentQuery, $expenseQuery, $advanceQuery)
    {
        $payments = (clone $paymentQuery)
            ->leftJoin('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->leftJoin('customers', 'customers.id', '=', 'payments.customer_id')
            ->selectRaw("'payment' as row_type")
            ->selectRaw('payments.id as row_id')
            ->selectRaw('payments.payment_date as ledger_date')
            ->selectRaw('0 as sort_type')
            ->selectRaw('payments.invoice_id as invoice_id')
            ->selectRaw('invoices.invoice_no as invoice_no')
            ->selectRaw('customers.name as party')
            ->selectRaw('payments.note as note')
            ->selectRaw('NULL as reference')
            ->selectRaw('NULL as category')
            ->selectRaw('payments.amount as credit')
            ->selectRaw('0 as debit')
            ->selectRaw('payments.amount as signed_amount')
            ->toBase();

        $expenses = (clone $expenseQuery)
            ->selectRaw("'expense' as row_type")
            ->selectRaw('expenses.id as row_id')
            ->selectRaw('expenses.expense_date as ledger_date')
            ->selectRaw('1 as sort_type')
            ->selectRaw('NULL as invoice_id')
            ->selectRaw('NULL as invoice_no')
            ->selectRaw('expenses.employee_name as party')
            ->selectRaw('expenses.note as note')
            ->selectRaw('expenses.reference as reference')
            ->selectRaw('expenses.category as category')
            ->selectRaw('0 as credit')
            ->selectRaw('expenses.amount as debit')
            ->selectRaw('-expenses.amount as signed_amount')
            ->toBase();

        $advances = (clone $advanceQuery)
            ->leftJoin('customers', 'customers.id', '=', 'customer_balance_transactions.customer_id')
            ->selectRaw("'advance' as row_type")
            ->selectRaw('customer_balance_transactions.id as row_id')
            ->selectRaw('customer_balance_transactions.transaction_date as ledger_date')
            ->selectRaw('2 as sort_type')
            ->selectRaw('NULL as invoice_id')
            ->selectRaw('NULL as invoice_no')
            ->selectRaw('customers.name as party')
            ->selectRaw('customer_balance_transactions.note as note')
            ->selectRaw('customer_balance_transactions.reference as reference')
            ->selectRaw('NULL as category')
            ->selectRaw('customer_balance_transactions.amount as credit')
            ->selectRaw('0 as debit')
            ->selectRaw('customer_balance_transactions.amount as signed_amount')
            ->toBase();

        return $payments->unionAll($expenses)->unionAll($advances);
    }
}
