@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Payment Note Default</h1>
        <div class="muted">This note appears on printed bills unless an invoice has its own payment note override.</div>
    </div>
    <div class="actions">
        <a class="btn light" href="{{ route('invoices.index') }}">Back to Invoices</a>
    </div>
</div>

<section class="card">
    <form method="post" action="{{ route('invoices.payment-note-default.update') }}" class="grid">
        @csrf
        @method('PUT')

        <div>
            <label for="payment_note">Default Payment Note</label>
            <textarea id="payment_note" name="payment_note" rows="5" required>{{ old('payment_note', $paymentNote) }}</textarea>
            <div class="muted" style="margin-top:8px;">Single invoices can still use a different note from the invoice create/edit form.</div>
        </div>

        <div class="actions">
            <button class="btn" type="submit">Save Default Note</button>
        </div>
    </form>
</section>
@endsection
