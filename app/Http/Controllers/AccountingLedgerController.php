<?php

namespace App\Http\Controllers;

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

        $payments = Payment::with(['customer', 'invoice', 'account'])
            ->when($from, fn ($query) => $query->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('payment_date', '<=', $to))
            ->get()
            ->map(fn (Payment $payment) => [
                'date' => $payment->payment_date,
                'type' => 'Payment',
                'customer' => $payment->customer->name,
                'reference' => $payment->invoice->invoice_no,
                'debit' => 0,
                'credit' => (float) $payment->amount,
                'note' => $payment->payment_method.($payment->account ? ' - '.$payment->account->account_number : ''),
                'url' => route('invoices.show', $payment->invoice),
            ]);

        $entries = $invoices->concat($payments)->sortBy('date')->values();
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return view('accounting.ledger', compact('entries', 'totalDebit', 'totalCredit'));
    }
}
