<?php

namespace App\Http\Controllers;

use App\Models\AccountDeposit;
use App\Models\CustomerBalanceTransaction;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            ->withSum('deposits as deposited_amount', 'amount')
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->get();

        $accounts = PaymentAccount::query()
            ->with('owner:id,name')
            ->withSum('payments as collected_amount', 'amount')
            ->withSum([
                'balanceTransactions as advance_collected_amount' => fn ($query) => $query
                    ->where('direction', 'credit')
                    ->whereNull('payment_id'),
            ], 'amount')
            ->withSum('expenses as spent_amount', 'amount')
            ->withSum('deposits as deposited_amount', 'amount')
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

    public function create(Request $request)
    {
        return view('payment_accounts.create', [
            'paymentAccount' => new PaymentAccount,
            'assignableOwners' => $this->assignableOwners($request),
        ]);
    }

    public function edit(Request $request, PaymentAccount $paymentAccount)
    {
        return view('payment_accounts.create', [
            'paymentAccount' => $paymentAccount,
            'assignableOwners' => $this->assignableOwners($request),
        ]);
    }

    public function show(Request $request, PaymentAccount $paymentAccount)
    {
        // With no date filter the ledger defaults to the last month so the
        // first page is recent activity rather than years of history. Merging
        // it into the (GET) request makes every downstream filter, the prior-
        // balance maths, and the pagination links pick it up unchanged.
        $defaultWindow = ! $request->filled('from') && ! $request->filled('to');
        if ($defaultWindow) {
            $request->merge(['from' => now()->subMonthNoOverflow()->toDateString()]);
        }

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
        $depositQuery = $this->filteredDepositQuery(
            AccountDeposit::query()->where('payment_account_id', $paymentAccount->id),
            $request
        );

        $perPage = $this->perPage($request);
        $filteredCollected = (float) (clone $paymentQuery)->sum('amount')
            + (float) (clone $advanceQuery)->sum('amount');
        $filteredSpent = (float) (clone $expenseQuery)->sum('amount');
        $filteredDeposited = (float) (clone $depositQuery)->sum('amount');
        [$ledgerRows, $runningBalance] = $this->paginatedLedgerRows(
            $paymentQuery,
            $expenseQuery,
            $advanceQuery,
            $depositQuery,
            $request,
            $perPage,
            (float) $paymentAccount->opening_balance,
            $this->priorCollectionTotal($paymentAccount->payments(), $request)
                + $this->priorAdvanceTotal($paymentAccount->balanceTransactions()
                    ->where('direction', 'credit')
                    ->whereNull('payment_id'), $request)
                - $this->priorExpenseTotal($paymentAccount->expenses(), $request)
                - $this->priorDepositTotal($paymentAccount->deposits(), $request)
        );
        $filteredTransactions = $ledgerRows->total();

        $totalCollected = $paymentAccount->payments()->sum('amount')
            + $paymentAccount->balanceTransactions()
                ->where('direction', 'credit')
                ->whereNull('payment_id')
                ->sum('amount');
        $totalSpent = $paymentAccount->expenses()->sum('amount');
        $totalDeposited = $paymentAccount->deposits()->sum('amount');

        return view('payment_accounts.show', compact('paymentAccount', 'ledgerRows', 'totalCollected', 'totalSpent', 'totalDeposited', 'filteredCollected', 'filteredSpent', 'filteredDeposited', 'filteredTransactions', 'runningBalance', 'defaultWindow'));
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
            null,
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
        $data = $this->validatedAccount($request);
        $data['owner_user_id'] = $this->resolveOwnerId($request, null);
        $data['balance_limit'] = $this->resolveBalanceLimit($request, null);

        PaymentAccount::create($data);

        return redirect()->route('payment-accounts.index')->with('success', 'Payment account created successfully.');
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $data = $this->validatedAccount($request);
        $data['owner_user_id'] = $this->resolveOwnerId($request, $paymentAccount);
        $data['balance_limit'] = $this->resolveBalanceLimit($request, $paymentAccount);

        if ($this->hasTransactions($paymentAccount)
            && abs((float) $data['opening_balance'] - (float) $paymentAccount->opening_balance) >= 0.005) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Opening balance cannot be changed after transactions have been recorded.',
            ]);
        }

        $paymentAccount->update($data);

        return redirect()->route('payment-accounts.index')->with('success', 'Payment account updated successfully.');
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        if ($this->hasTransactions($paymentAccount)) {
            return back()->withErrors(['payment_account' => 'An account with payments, advances, or expenses cannot be deleted. Set it to inactive instead.']);
        }

        $paymentAccount->delete();

        return redirect()->route('payment-accounts.index')->with('success', 'Payment account deleted successfully.');
    }

    private function validatedAccount(Request $request): array
    {
        return $request->validate([
            'payment_method' => ['required', 'in:bkash,nagad,bank'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    /** Users a super admin may assign as the owner of an account. */
    private function assignableOwners(Request $request): Collection
    {
        if (! $request->user()?->isSuperAdmin()) {
            return new Collection;
        }

        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * Only a super admin may set or move ownership. Everyone else keeps the
     * existing owner (editing) or becomes the owner themselves (creating).
     */
    private function resolveOwnerId(Request $request, ?PaymentAccount $account): ?int
    {
        if ($request->user()?->isSuperAdmin() && $request->has('owner_user_id')) {
            $ownerId = $request->validate([
                'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            ])['owner_user_id'];

            return $ownerId !== null ? (int) $ownerId : null;
        }

        return $account?->owner_user_id ?? $request->user()?->id;
    }

    /**
     * Only a super admin may set an account's balance limit. Everyone else
     * keeps whatever limit is already on the account.
     */
    private function resolveBalanceLimit(Request $request, ?PaymentAccount $account): ?float
    {
        if ($request->user()?->isSuperAdmin() && $request->has('balance_limit')) {
            $limit = $request->validate([
                'balance_limit' => ['nullable', 'numeric', 'min:0'],
            ])['balance_limit'];

            return $limit === null || $limit === '' ? null : (float) $limit;
        }

        return $account?->balance_limit !== null ? (float) $account->balance_limit : null;
    }

    private function hasTransactions(PaymentAccount $paymentAccount): bool
    {
        return $paymentAccount->payments()->exists()
            || $paymentAccount->expenses()->exists()
            || $paymentAccount->balanceTransactions()->exists()
            || $paymentAccount->deposits()->exists();
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

    private function filteredDepositQuery($query, Request $request)
    {
        return $query
            ->when($request->filled('from'), fn ($query) => $query->where('account_deposits.deposited_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->where('account_deposits.deposited_at', '<=', $request->input('to')))
            ->when($request->filled('min_amount'), fn ($query) => $query->where('account_deposits.amount', '>=', (float) $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($query) => $query->where('account_deposits.amount', '<=', (float) $request->input('max_amount')))
            ->when(trim((string) $request->input('search')) !== '', function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('account_deposits.note', 'like', "%{$search}%")
                        ->orWhere('account_deposits.reference', 'like', "%{$search}%");
                });
            });
    }

    private function priorDepositTotal($query, Request $request): float
    {
        if (! $request->filled('from')) {
            return 0;
        }

        return (float) $query->where('account_deposits.deposited_at', '<', $request->input('from'))->sum('amount');
    }

    private function paginatedLedgerRows($paymentQuery, $expenseQuery, $advanceQuery, $depositQuery, Request $request, int $perPage, float $openingBalance, float $priorDateBalance): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;
        $startingBalance = $openingBalance + $priorDateBalance;

        $orderedRows = DB::query()
            ->fromSub($this->ledgerQuery($paymentQuery, $expenseQuery, $advanceQuery, $depositQuery), 'ledger_rows')
            ->orderBy('ledger_date')
            ->orderBy('ledger_created_at')
            ->orderBy('sort_type')
            ->orderBy('row_id');

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
                    'deposit' => 'Deposit to office',
                    default => $expenseLabel,
                };

                return [
                    'type' => $type,
                    'date' => $row->ledger_date ? Carbon::parse($row->ledger_date) : null,
                    'entered_at' => $row->ledger_created_at ? Carbon::parse($row->ledger_created_at) : null,
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

    private function ledgerQuery($paymentQuery, $expenseQuery, $advanceQuery, $depositQuery = null)
    {
        $payments = (clone $paymentQuery)
            ->leftJoin('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->leftJoin('customers', 'customers.id', '=', 'payments.customer_id')
            ->selectRaw("'payment' as row_type")
            ->selectRaw('payments.id as row_id')
            ->selectRaw('payments.payment_date as ledger_date')
            ->selectRaw('payments.created_at as ledger_created_at')
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
            ->selectRaw('expenses.created_at as ledger_created_at')
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
            ->selectRaw('customer_balance_transactions.created_at as ledger_created_at')
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

        $ledger = $payments->unionAll($expenses)->unionAll($advances);

        if ($depositQuery !== null) {
            $deposits = (clone $depositQuery)
                ->selectRaw("'deposit' as row_type")
                ->selectRaw('account_deposits.id as row_id')
                ->selectRaw('account_deposits.deposited_at as ledger_date')
                ->selectRaw('account_deposits.created_at as ledger_created_at')
                ->selectRaw('3 as sort_type')
                ->selectRaw('NULL as invoice_id')
                ->selectRaw('NULL as invoice_no')
                ->selectRaw("'Office' as party")
                ->selectRaw('account_deposits.note as note')
                ->selectRaw('account_deposits.reference as reference')
                ->selectRaw('NULL as category')
                ->selectRaw('0 as credit')
                ->selectRaw('account_deposits.amount as debit')
                ->selectRaw('-account_deposits.amount as signed_amount')
                ->toBase();

            $ledger = $ledger->unionAll($deposits);
        }

        return $ledger;
    }
}
