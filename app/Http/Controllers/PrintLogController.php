<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PrintLog;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrintLogController extends Controller
{
    private const TYPES = ['invoice' => Invoice::class, 'invoice_pdf' => Invoice::class, 'delivery_challan' => Invoice::class, 'invoice_quotation' => Invoice::class, 'quotation' => Quotation::class, 'payment_voucher' => Payment::class, 'payment_thermal_voucher' => Payment::class, 'expense_voucher' => Expense::class];

    public function index(Request $request)
    {
        $logs = PrintLog::with(['organization', 'user'])->latest('printed_at')->paginate(50)->withQueryString();
        return view('print_logs.index', compact('logs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['organization_id' => ['required', 'exists:organizations,id'], 'document_type' => ['required', Rule::in(array_keys(self::TYPES))], 'printable_id' => ['required', 'integer']]);
        $organization = Organization::where('is_active', true)->findOrFail($data['organization_id']);
        $class = self::TYPES[$data['document_type']];
        $printable = $class::findOrFail($data['printable_id']);
        $documentNo = $printable->invoice_no ?? $printable->quotation_no ?? ($printable instanceof Payment ? 'PAY-'.$printable->id : 'EXP-'.$printable->id);
        $log = PrintLog::create(['organization_id' => $organization->id, 'printable_type' => $class, 'printable_id' => $printable->id, 'document_type' => $data['document_type'], 'document_no' => $documentNo, 'user_id' => $request->user()?->id, 'user_name' => $request->user()?->name, 'printed_at' => now(), 'ip_address' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 2000)]);
        return response()->json(['id' => $log->id, 'printed_at' => $log->printed_at->toDateTimeString()]);
    }
}
