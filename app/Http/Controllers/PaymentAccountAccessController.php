<?php

namespace App\Http\Controllers;

use App\Models\PaymentAccount;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Super-admin console for deciding which admins may record money through an
 * account they do not own.
 */
class PaymentAccountAccessController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $accounts = PaymentAccount::query()
            ->with(['owner:id,name', 'delegates:id,name'])
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->get();

        return view('payment_account_access.index', [
            'accounts' => $accounts,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // The owner always has access; never store them as a delegate too.
        $delegateIds = collect($data['user_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === (int) $paymentAccount->owner_user_id)
            ->unique()
            ->values()
            ->all();

        $paymentAccount->delegates()->sync($delegateIds);

        return back()->with('success', 'Access updated for "'.$paymentAccount->account_name.'".');
    }
}
