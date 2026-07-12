<?php

namespace App\Observers;

use App\Models\RecordVersion;
use Illuminate\Database\Eloquent\Model;

class RecordVersionObserver
{
    private static int $suppressionDepth = 0;

    public static function withoutRecording(callable $callback): mixed
    {
        self::$suppressionDepth++;

        try {
            return $callback();
        } finally {
            self::$suppressionDepth--;
        }
    }

    public function updated(Model $model): void
    {
        if (self::$suppressionDepth > 0) {
            return;
        }

        $dirty = collect($model->getChanges())
            ->except(['updated_at'])
            ->all();

        if ($dirty === []) {
            return;
        }

        $user = auth()->user();
        $changedFields = array_keys($dirty);
        $oldValues = [];
        $newValues = [];

        foreach ($changedFields as $field) {
            $oldValues[$field] = $this->isSensitiveField($field) ? '[hidden]' : $this->normalizeValue($model->getOriginal($field));
            $newValues[$field] = $this->isSensitiveField($field) ? '[hidden]' : $this->normalizeValue($model->getAttribute($field));
        }

        RecordVersion::create([
            'versionable_type' => $model::class,
            'versionable_id' => $model->getKey(),
            'table_name' => $model->getTable(),
            'action' => 'updated',
            'edited_by' => $user ? (string) $user->id : 'system',
            'edited_by_type' => $user ? 'user' : 'system',
            'edited_by_name' => $user?->name ?? 'System',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'metadata' => [
                'source' => 'model_update',
            ],
        ]);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    private function isSensitiveField(string $field): bool
    {
        foreach (['password', 'token', 'secret', 'key'] as $needle) {
            if (str_contains(strtolower($field), $needle)) {
                return true;
            }
        }

        return false;
    }
}
