<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PrintContextService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountingLedgerController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->ledgerData($request);
        $allEntries = $data['entries'];
        $perPageDefault = 50;
        $perPageOptions = [25, 50, 100, 200];
        $perPage = $this->perPage($request, $perPageDefault, $perPageOptions);
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $data['entries'] = new LengthAwarePaginator(
            $allEntries->forPage($page, $perPage)->values(),
            $allEntries->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->except(['page', 'make_per_page_default']),
            ],
        );

        return view('accounting.ledger', array_merge($data, compact(
            'perPage',
            'perPageDefault',
            'perPageOptions',
        )));
    }

    public function print(Request $request, PrintContextService $printContext)
    {
        return view('accounting.ledger_print', array_merge(
            $this->ledgerData($request),
            $printContext->for($request),
        ));
    }

    private function ledgerData(Request $request): array
    {
        $from = $request->date('from');
        $to = $request->date('to');
        $customerId = $request->integer('customer_id') ?: null;
        $selectedCustomer = $customerId ? Customer::find($customerId) : null;

        if ($customerId && ! $selectedCustomer) {
            abort(404);
        }

        if (! $request->user()?->hasPermission('manage_payment_accounts') && ! $selectedCustomer) {
            abort(403, 'You do not have permission to access the full accounting ledger.');
        }

        $canOpenInvoices = $request->user()?->hasPermission('manage_invoices');
        $canOpenPayments = $request->user()?->hasPermission('manage_payments');
        $canOpenCustomers = $request->user()?->hasPermission('manage_customers');
        $canOpenExpenses = $request->user()?->hasPermission('manage_expenses');

        $invoices = Invoice::with(['customer' => fn ($query) => $query->withTrashed()])
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->get()
            ->map(fn (Invoice $invoice) => [
                'date' => $this->ledgerDateTime($invoice->created_at, $invoice->created_at),
                'sort_order' => 10,
                'source_id' => $invoice->id,
                'type' => 'Invoice',
                'customer' => $this->customerLabel($invoice->customer?->name, $invoice->customer_id),
                'reference' => $invoice->invoice_no,
                'debit' => (float) $invoice->total,
                'credit' => 0,
                'note' => ucfirst($invoice->invoice_type ?? 'service').' bill',
                'url' => $canOpenInvoices ? route('invoices.show', $invoice) : null,
            ]);

        $payments = Payment::with([
            'customer' => fn ($query) => $query->withTrashed(),
            'invoice',
            'account',
            'allocations.invoice',
        ])
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->when($from, fn ($query) => $query->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('payment_date', '<=', $to))
            ->get()
            ->map(function (Payment $payment) use ($canOpenInvoices, $canOpenPayments) {
                $allocationSummary = $payment->allocations
                    ->map(fn ($allocation) => ($allocation->invoice?->invoice_no ?? 'Deleted invoice').' '.number_format((float) $allocation->amount, 2))
                    ->join(', ');

                return [
                    'date' => $this->ledgerDateTime($payment->payment_date, $payment->created_at),
                    'sort_order' => 20,
                    'source_id' => $payment->id,
                    'type' => 'Payment',
                    'customer' => $this->customerLabel($payment->customer?->name, $payment->customer_id),
                    'reference' => 'Payment #'.$payment->id,
                    'debit' => 0,
                    'credit' => (float) $payment->amount,
                    'note' => $payment->payment_method
                        .($payment->account ? ' - '.$payment->account->account_name : '')
                        .($allocationSummary ? ' | Allocated: '.$allocationSummary : ' | Added to advance'),
                    'url' => $canOpenPayments
                        ? route('payments.show', $payment)
                        : ($canOpenInvoices && $payment->invoice ? route('invoices.show', $payment->invoice) : null),
                ];
            });

        $balanceEntries = CustomerBalanceTransaction::with([
            'customer' => fn ($query) => $query->withTrashed(),
            'account',
        ])
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->where(function ($query) {
                $query->where('direction', 'debit')
                    ->orWhere(function ($query) {
                        $query->where('direction', 'credit')->whereNull('payment_id');
                    });
            })
            ->when($from, fn ($query) => $query->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('transaction_date', '<=', $to))
            ->get()
            ->map(function (CustomerBalanceTransaction $transaction) use ($canOpenCustomers) {
                $isCredit = $transaction->direction === 'credit';

                return [
                    'date' => $this->ledgerDateTime($transaction->transaction_date, $transaction->created_at),
                    'sort_order' => 30,
                    'source_id' => $transaction->id,
                    'type' => $isCredit ? 'Advance' : 'Advance Used',
                    'customer' => $this->customerLabel($transaction->customer?->name, $transaction->customer_id),
                    'reference' => $transaction->reference ?: 'Advance #'.$transaction->id,
                    'debit' => 0,
                    'credit' => $isCredit ? (float) $transaction->amount : 0,
                    'note' => ($transaction->payment_method ?: 'advance')
                        .($transaction->account ? ' - '.$transaction->account->account_name : '')
                        .' | '.($isCredit ? 'Added to party balance' : 'Used from party balance: '.number_format((float) $transaction->amount, 2)),
                    'url' => $canOpenCustomers && $transaction->customer && ! $transaction->customer->trashed()
                        ? route('customers.show', $transaction->customer)
                        : null,
                ];
            });

        $expenses = Expense::with('account')
            ->when($customerId, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($from, fn ($query) => $query->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('expense_date', '<=', $to))
            ->get()
            ->map(fn (Expense $expense) => [
                'date' => $this->ledgerDateTime($expense->expense_date, $expense->created_at),
                'sort_order' => 40,
                'source_id' => $expense->id,
                'type' => $expense->expense_type === 'salary' ? 'Salary' : 'Expense',
                'customer' => $expense->employee_name ?: 'Business Expense',
                'reference' => $expense->reference ?: 'Expense #'.$expense->id,
                'debit' => (float) $expense->amount,
                'credit' => 0,
                'note' => (Expense::CATEGORIES[$expense->category] ?? ucfirst($expense->category))
                    .' | '.$expense->payment_method
                    .($expense->account ? ' - '.$expense->account->account_name : ''),
                'url' => $canOpenExpenses ? route('expenses.show', $expense) : null,
            ]);

        $entries = $invoices
            ->concat($payments)
            ->concat($balanceEntries)
            ->concat($expenses)
            ->sortBy([
                ['date', 'asc'],
                ['sort_order', 'asc'],
                ['source_id', 'asc'],
            ])
            ->values();
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');
        $runningBalance = 0.0;
        $entries = $entries->map(function (array $entry, int $index) use (&$runningBalance): array {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['serial'] = $index + 1;
            $entry['balance'] = $runningBalance;

            return $entry;
        });

        return compact(
            'entries',
            'totalDebit',
            'totalCredit',
            'selectedCustomer',
            'from',
            'to',
        );
    }

    private function ledgerDateTime($businessDate, $createdAt)
    {
        return $businessDate->copy()->setTime(
            $createdAt->hour,
            $createdAt->minute,
            $createdAt->second,
            $createdAt->micro,
        );
    }

    private function customerLabel(?string $name, int $customerId): string
    {
        return filled($name) ? $name : 'Deleted party #'.$customerId;
    }
}
