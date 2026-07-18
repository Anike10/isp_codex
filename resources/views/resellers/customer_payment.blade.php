@extends('layouts.app')

@section('content')
<style>
    .wallet-pay-page{max-width:1050px;margin:0 auto}.wallet-pay-hero{padding:24px;border-radius:16px;color:#fff;background:linear-gradient(125deg,#102a43,#116149 60%,#1d76c9);box-shadow:0 15px 32px rgba(16,42,67,.18)}
    .wallet-pay-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}.wallet-pay-hero h1{margin:0;font-size:28px}.wallet-pay-hero p{margin:8px 0 0;color:#d5e7f3}
    .wallet-pay-hero .btn{background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.22)}
    .wallet-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:18px}.wallet-summary>div{padding:14px;border:1px solid rgba(255,255,255,.15);border-radius:10px;background:rgba(255,255,255,.1)}
    .wallet-summary span{display:block;color:#c7deed;font-size:11px;font-weight:800;text-transform:uppercase}.wallet-summary strong{display:block;margin-top:8px;font-size:19px}
    .wallet-payment-layout{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(300px,.85fr);gap:16px;margin-top:16px}.wallet-payment-card{border-color:#dce6ef;border-radius:14px;box-shadow:0 5px 16px rgba(15,23,42,.045)}
    .wallet-payment-card h2{display:flex;align-items:center;gap:9px;font-size:18px}.wallet-payment-card h2:before{width:4px;height:21px;border-radius:99px;background:#116149;content:""}
    .wallet-rule{padding:14px;border:1px solid #b9e2ce;border-radius:10px;background:#effaf5;color:#175b43;line-height:1.55}.wallet-rule strong{display:block;margin-bottom:4px}
    .wallet-form{display:grid;gap:14px}.wallet-form input,.wallet-form textarea{width:100%}.wallet-form button{min-height:44px;font-weight:800}
    .commission-choice{display:flex;gap:12px;align-items:flex-start;padding:15px;border:1px solid #d7e3ec;border-radius:11px;background:#f7fafc;cursor:pointer}.commission-choice input{width:19px;height:19px;margin-top:2px;accent-color:#116149}.commission-choice strong{display:block}.commission-choice span{display:block;margin-top:4px;color:#667085;font-size:12px;line-height:1.45}.commission-choice:has(input:checked){border-color:#f0b44d;background:#fff8e8}.payable-highlight{padding:12px 14px;border-radius:10px;background:#eef8f4;color:#116149}.payable-highlight strong{font-size:19px}
    .calc-card{position:relative;overflow:hidden;background:linear-gradient(180deg,#fff 0%,#f8fbfd 100%)}.calc-card:after{position:absolute;top:-80px;right:-80px;width:180px;height:180px;border-radius:50%;background:rgba(29,118,201,.055);content:""}.calc-package{position:relative;z-index:1;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 15px;border:1px solid #dce7f0;border-radius:11px;background:#fff}.calc-package-label{color:#667085;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.calc-package strong{display:block;margin-top:4px;font-size:18px}.calc-package-badge{padding:7px 10px;border-radius:999px;background:#e9f5ff;color:#175cd3;font-size:12px;font-weight:800;white-space:nowrap}
    .price-breakdown{position:relative;z-index:1;margin-top:13px;padding:16px;border:1px solid #dce7f0;border-radius:12px;background:#fff}.price-row{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:9px 0}.price-row span{color:#667085;font-size:13px}.price-row strong{font-size:15px;white-space:nowrap}.price-row.discount strong{color:#b54708}.price-row.total{margin-top:7px;padding:15px 0 2px;border-top:1px dashed #b8c8d6}.price-row.total span{color:#172033;font-weight:800}.price-row.total strong{color:#116149;font-size:27px;letter-spacing:-.03em}.discount-meta{display:block;margin-top:3px!important;color:#98a2b3!important;font-size:11px!important}.calc-rules{position:relative;z-index:1;display:grid;gap:9px;margin-top:14px}.calc-rule{display:grid;grid-template-columns:25px 1fr;gap:9px;align-items:start;padding:10px 11px;border-radius:9px;background:#f2f7fa;color:#475467;font-size:12px;line-height:1.45}.calc-rule:before{display:grid;width:22px;height:22px;place-items:center;border-radius:50%;background:#dff4e9;color:#087443;font-weight:900;content:"✓"}.calc-rule.warning:before{background:#e9f1ff;color:#175cd3;content:"i"}
    @media(max-width:760px){.wallet-payment-layout{grid-template-columns:1fr}.wallet-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.wallet-pay-head{display:grid}}
    @media(max-width:430px){.wallet-summary{grid-template-columns:1fr}}
</style>

<div class="wallet-pay-page">
    <section class="wallet-pay-hero">
        <div class="wallet-pay-head">
            <div>
                <h1>Pay from Reseller Advance</h1>
                <p>{{ $customer->name }} · {{ $customer->connection_id ?? $customer->phone }}</p>
            </div>
            <a class="btn" href="{{ route('reseller.dashboard') }}">Back to Dashboard</a>
        </div>
        <div class="wallet-summary">
            <div><span>Wallet Balance</span><strong>৳ {{ number_format((float) $reseller->account_balance, 2) }}</strong></div>
            <div><span>Available Today</span><strong>৳ {{ number_format($walletAvailable, 2) }}</strong></div>
            <div><span>Party Due</span><strong>৳ {{ number_format($dueTotal, 2) }}</strong></div>
            <div><span>Commission</span><strong>{{ number_format((float) $reseller->reseller_commission_percent, 2) }}%</strong></div>
        </div>
    </section>

    <div class="wallet-payment-layout">
        <section class="card wallet-payment-card">
            <h2>Wallet Payment</h2>
            <div class="wallet-rule">
                <strong>No cash/bank account entry will be created.</strong>
                This amount is deducted only from the reseller's existing advance balance and allocated to the party invoice.
            </div>
            <form method="post" action="{{ route('reseller.customers.payments.store', $customer) }}" class="wallet-form" style="margin-top:16px">
                @csrf
                <input type="hidden" name="operation_key" value="{{ (string) Illuminate\Support\Str::uuid() }}">
                <div>
                    <label>Amount from advance</label>
                    <input id="walletPaymentAmount" type="text" inputmode="decimal" name="amount" value="{{ old('amount', number_format($suggestedAmount, 2, '.', '')) }}" required>
                    <span class="muted">Maximum currently available: ৳ {{ number_format($walletAvailable, 2) }}</span>
                </div>
                <label class="commission-choice">
                    <input id="withoutCommission" type="checkbox" name="without_commission" value="1" @checked(old('without_commission'))>
                    <span><strong>Without Commission</strong><span>Charge the full package price. No reseller commission discount will be deducted from this invoice.</span></span>
                </label>
                <div class="payable-highlight">Selected invoice payable: <strong id="selectedPayable">৳ {{ number_format(max(0, $grossPackagePrice - $commissionAmount), 2) }}</strong></div>
                <div>
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required>
                </div>
                <div>
                    <label>Note</label>
                    <textarea name="note" placeholder="Optional payment note">{{ old('note') }}</textarea>
                </div>
                <button class="btn" type="submit" @disabled($walletAvailable <= 0 || $suggestedAmount <= 0)>Deduct from Advance & Pay Invoice</button>
            </form>
        </section>

        <aside class="card wallet-payment-card calc-card">
            <h2>Invoice Calculation</h2>
            <div class="calc-package">
                <div><span class="calc-package-label">Selected package</span><strong>{{ $customer->activeSubscription?->package?->name ?? 'Not assigned' }}</strong></div>
                <span class="calc-package-badge">Monthly Plan</span>
            </div>
            <div class="price-breakdown">
                <div class="price-row"><span>Full package price</span><strong>৳ {{ number_format($grossPackagePrice, 2) }}</strong></div>
                <div class="price-row discount"><span>Commission discount<small id="calculationDiscountMeta" class="discount-meta">{{ number_format((float) $reseller->reseller_commission_percent, 2) }}% reseller benefit</small></span><strong id="calculationDiscount">− ৳ {{ number_format($commissionAmount, 2) }}</strong></div>
                <div class="price-row total"><span>Expected payable</span><strong id="calculationPayable">৳ {{ number_format(max(0, $grossPackagePrice - $commissionAmount), 2) }}</strong></div>
            </div>
            <div class="calc-rules">
                <div class="calc-rule">Missing current invoice is generated automatically.</div>
                <div class="calc-rule">Commission choice is saved as the invoice snapshot.</div>
                <div class="calc-rule">The reseller wallet receives one debit transaction.</div>
                <div class="calc-rule warning">No second cash, bKash, bank or payment-account receipt is created.</div>
            </div>
        </aside>
    </div>
</div>
<script>
const withoutCommission = document.getElementById('withoutCommission');
const walletPaymentAmount = document.getElementById('walletPaymentAmount');
const selectedPayable = document.getElementById('selectedPayable');
const calculationPayable = document.getElementById('calculationPayable');
const calculationDiscount = document.getElementById('calculationDiscount');
const calculationDiscountMeta = document.getElementById('calculationDiscountMeta');
const grossPayable = {{ json_encode(round($grossPackagePrice, 2)) }};
const netPayable = {{ json_encode(round(max(0, $grossPackagePrice - $commissionAmount), 2)) }};
const walletAvailable = {{ json_encode(round($walletAvailable, 2)) }};
const formatMoney = value => new Intl.NumberFormat('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}).format(value);
function refreshCommissionChoice() {
    const payable = withoutCommission.checked ? grossPayable : netPayable;
    selectedPayable.textContent = '৳ ' + formatMoney(payable);
    calculationPayable.textContent = '৳ ' + formatMoney(payable);
    calculationDiscount.textContent = withoutCommission.checked ? '− ৳ 0.00' : '− ৳ ' + formatMoney(grossPayable - netPayable);
    calculationDiscountMeta.textContent = withoutCommission.checked ? 'Without Commission selected' : '{{ number_format((float) $reseller->reseller_commission_percent, 2) }}% reseller benefit';
    walletPaymentAmount.value = Math.min(payable, walletAvailable).toFixed(2);
}
withoutCommission.addEventListener('change', refreshCommissionChoice);
if (withoutCommission.checked) refreshCommissionChoice();
</script>
@endsection
