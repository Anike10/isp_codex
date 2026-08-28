<?php

namespace App\Services;

use App\Models\PaymentAccount;
use App\Models\User;

class PaymentAccountPreferenceService
{
    private const METHODS = ['cash', 'bkash', 'nagad', 'bank'];

    /**
     * @return array{payment_method: string, payment_account_id: int|null}
     */
    public function forUser(?User $user): array
    {
        $fallback = [
            'payment_method' => 'cash',
            'payment_account_id' => null,
        ];

        if (! $user || ! in_array($user->default_payment_method, self::METHODS, true)) {
            return $fallback;
        }

        if ($user->default_payment_method === 'cash') {
            return $fallback;
        }

        $account = PaymentAccount::query()
            ->whereKey($user->default_payment_account_id)
            ->where('payment_method', $user->default_payment_method)
            ->where('status', 'active')
            ->usableBy($user)
            ->first();

        if (! $account) {
            return $fallback;
        }

        return [
            'payment_method' => $account->payment_method,
            'payment_account_id' => $account->id,
        ];
    }

    public function remember(?User $user, bool $requested, string $paymentMethod, ?int $paymentAccountId): void
    {
        if (! $user || ! $requested || ! in_array($paymentMethod, self::METHODS, true)) {
            return;
        }

        if ($paymentMethod === 'cash') {
            $paymentAccountId = null;
        } else {
            $accountExists = PaymentAccount::query()
                ->whereKey($paymentAccountId)
                ->where('payment_method', $paymentMethod)
                ->where('status', 'active')
                ->usableBy($user)
                ->exists();

            if (! $accountExists) {
                return;
            }
        }

        $user->forceFill([
            'default_payment_method' => $paymentMethod,
            'default_payment_account_id' => $paymentAccountId,
        ])->save();
    }
}
