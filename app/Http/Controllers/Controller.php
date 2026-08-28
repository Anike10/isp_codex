<?php

namespace App\Http\Controllers;

use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    /**
     * Stop a collection that would push a capped payment account past its
     * balance limit, telling the operator how much to deposit to the office
     * first. No-op for uncapped accounts.
     */
    protected function assertAccountCanReceive(PaymentAccount $account, float $amount): void
    {
        if (! $account->wouldExceedLimit($amount)) {
            return;
        }

        $shortBy = round($account->liveBalance() + $amount - (float) $account->balance_limit, 2);

        throw ValidationException::withMessages([
            'payment_account_id' => sprintf(
                'This account has hit its balance limit of BDT %s. Record a deposit to office of at least BDT %s before collecting more into it.',
                number_format((float) $account->balance_limit, 2),
                number_format(max($shortBy, 0.01), 2),
            ),
        ]);
    }

    protected function isValidPerPage(int $perPage, array $options, int $maxPerPage = 20000): bool
    {
        return in_array($perPage, $options, true) || ($perPage > 0 && $perPage <= $maxPerPage);
    }

    protected function perPage(Request $request, int $default = 50, array $options = [25, 50, 100, 200]): int
    {
        $maxPerPage = 20000;
        $sessionKey = 'per_page_default.'.($request->route()?->getName() ?: $request->path());
        $storedDefault = (int) $request->session()->get($sessionKey, $default);
        if ($this->isValidPerPage($storedDefault, $options, $maxPerPage)) {
            $default = $storedDefault;
        }

        $perPage = (int) $request->query('per_page', $default);
        if (! $this->isValidPerPage($perPage, $options, $maxPerPage)) {
            $perPage = $default;
        }

        if ($request->query('make_per_page_default') === '1' && $this->isValidPerPage($perPage, $options, $maxPerPage)) {
            $request->session()->put($sessionKey, $perPage);
        }

        return $perPage;
    }
}
