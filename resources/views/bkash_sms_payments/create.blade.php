@extends('layouts.app')

@section('content')
<div class="topbar">
    <div>
        <h1>Manual bKash SMS Entry</h1>
        <div class="muted">Paste a bKash received payment SMS to parse and record it</div>
    </div>
    <a class="btn light" href="{{ route('bkash-sms-payments.index') }}">Back</a>
</div>

<form method="post" action="{{ route('bkash-sms-payments.store') }}" class="card form-grid">
    @csrf
    <div>
        <label>SMS Sender</label>
        <input name="sender" value="{{ old('sender', 'bKash') }}">
    </div>
    <div class="full">
        <label>bKash SMS</label>
        <textarea name="message" required placeholder="You have received Tk 10.00 from 01812707070. Ref test_ref. Fee Tk 0.00. Balance Tk 20,218.58. TrxID DE67UJKH01 at 06/05/2026 09:40">{{ old('message') }}</textarea>
    </div>
    <div class="full">
        <button class="btn" type="submit">Parse and Save SMS</button>
    </div>
</form>
@endsection
