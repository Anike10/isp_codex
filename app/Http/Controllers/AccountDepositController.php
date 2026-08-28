<?php

namespace App\Http\Controllers;

use App\Models\AccountDeposit;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;

class AccountDepositController extends Controller
{
    public function create(Request $request, PaymentAccount $paymentAccount)
    {
        $this->authorizeAccount($request, $paymentAccount);

        return view('account_deposits.create', [
            'account' => $paymentAccount,
            'liveBalance' => $paymentAccount->liveBalance(),
        ]);
    }

    public function store(Request $request, PaymentAccount $paymentAccount)
    {
        $this->authorizeAccount($request, $paymentAccount);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'deposited_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $liveBalance = $paymentAccount->liveBalance();

        if (round((float) $data['amount'], 2) > round($liveBalance, 2) + 0.001) {
            return back()->withInput()->withErrors([
                'amount' => sprintf('The deposit cannot be more than the account balance (BDT %s).', number_format($liveBalance, 2)),
            ]);
        }

        AccountDeposit::create([
            'payment_account_id' => $paymentAccount->id,
            'deposited_by' => $request->user()?->id,
            'amount' => $data['amount'],
            'deposited_at' => $data['deposited_at'],
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('payment-accounts.show', $paymentAccount)
            ->with('success', 'Office deposit of BDT '.number_format((float) $data['amount'], 2).' recorded.');
    }

    /** Only the account owner or a super admin may hand its money to the office. */
    private function authorizeAccount(Request $request, PaymentAccount $paymentAccount): void
    {
        $user = $request->user();
        $allowed = $user?->isSuperAdmin()
            || ($user && (int) $paymentAccount->owner_user_id === (int) $user->id);

        abort_unless($allowed, 403, 'You cannot record deposits for this account.');
    }
}
