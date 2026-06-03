<?php

namespace App\Http\Controllers;

use App\Models\CustomerBalanceTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class AccountingLedgerController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from');
        $to = $request->date('to');

        $invoices = Invoice::with('customer')
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->get()
            ->map(fn (Invoice $invoice) => [
                'date' => $invoice->created_at,
                'type' => 'Invoice',
                'customer' => $invoice->customer->name,
                'reference' => $invoice->invoice_no,
                'debit' => (float) $invoice->total,
                'credit' => 0,
                'note' => ucfirst($invoice->invoice_type ?? 'service').' bill',
                'url' => route('invoices.show', $invoice),
            ]);

        $payments = Payment::with(['customer', 'invoice', 'account', 'allocations.invoice'])
            ->when($from, fn ($query) => $query->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('payment_date', '<=', $to))
            ->get()
            ->map(function (Payment $payment) {
                $allocationSummary = $payment->allocations
                    ->map(fn ($allocation) => $allocation->invoice->invoice_no.' '.number_format((float) $allocation->amount, 2))
                    ->join(', ');

                return [
                    'date' => $payment->payment_date,
                    'type' => 'Payment',
                    'customer' => $payment->customer->name,
                    'reference' => 'Payment #'.$payment->id,
                    'debit' => 0,
                    'credit' => (float) $payment->amount,
                    'note' => $payment->payment_method
                        .($payment->account ? ' - '.$payment->account->account_name : '')
                        .($allocationSummary ? ' | Allocated: '.$allocationSummary : ' | Added to advance'),
                    'url' => route('invoices.show', $payment->invoice),
                ];
            });

        $balanceEntries = CustomerBalanceTransaction::with(['customer', 'account'])
            ->where(function ($query) {
                $query->where('direction', 'debit')
                    ->orWhere(function ($query) {
                        $query->where('direction', 'credit')->whereNull('payment_id');
                    });
            })
            ->when($from, fn ($query) => $query->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('transaction_date', '<=', $to))
            ->get()
            ->map(function (CustomerBalanceTransaction $transaction) {
                $isCredit = $transaction->direction === 'credit';

                return [
                    'date' => $transaction->transaction_date,
                    'type' => $isCredit ? 'Advance' : 'Advance Used',
                    'customer' => $transaction->customer->name,
                    'reference' => $transaction->reference ?: 'Advance #'.$transaction->id,
                    'debit' => 0,
                    'credit' => $isCredit ? (float) $transaction->amount : 0,
                    'note' => ($transaction->payment_method ?: 'advance')
                        .($transaction->account ? ' - '.$transaction->account->account_name : '')
                        .' | '.($isCredit ? 'Added to party balance' : 'Used from party balance: '.number_format((float) $transaction->amount, 2)),
                    'url' => route('customers.show', $transaction->customer),
                ];
            });

        $expenses = Expense::with('account')
            ->when($from, fn ($query) => $query->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('expense_date', '<=', $to))
            ->get()
            ->map(fn (Expense $expense) => [
                'date' => $expense->expense_date,
                'type' => $expense->expense_type === 'salary' ? 'Salary' : 'Expense',
                'customer' => $expense->employee_name ?: 'Business Expense',
                'reference' => $expense->reference ?: 'Expense #'.$expense->id,
                'debit' => (float) $expense->amount,
                'credit' => 0,
                'note' => (Expense::CATEGORIES[$expense->category] ?? ucfirst($expense->category))
                    .' | '.$expense->payment_method
                    .($expense->account ? ' - '.$expense->account->account_name : ''),
                'url' => route('expenses.show', $expense),
            ]);

        $entries = $invoices->concat($payments)->concat($balanceEntries)->concat($expenses)->sortBy('date')->values();
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return view('accounting.ledger', compact('entries', 'totalDebit', 'totalCredit'));
    }
}
