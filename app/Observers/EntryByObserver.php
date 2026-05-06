<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EntryByObserver
{
    public function creating(Model $model): void
    {
        if (! Schema::hasColumn($model->getTable(), 'entry_by')) {
            return;
        }

        if (! $model->getAttribute('entry_by')) {
            $model->setAttribute('entry_by', Auth::check() ? (string) Auth::id() : 'system');
        }

        if (! Schema::hasColumn($model->getTable(), 'entry_by_type') || $model->getAttribute('entry_by_type')) {
            return;
        }

        if (Auth::check() && $model->getAttribute('entry_by') === (string) Auth::id()) {
            $model->setAttribute('entry_by_type', 'user');
        } elseif ($model->getAttribute('entry_by') === 'system') {
            $model->setAttribute('entry_by_type', 'system');
        } elseif ($model->getAttribute('entry_by')) {
            $model->setAttribute('entry_by_type', 'sms_device');
        }
    }
}
