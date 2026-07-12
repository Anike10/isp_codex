<?php

namespace App\Services;

use App\Models\RecordVersion;
use Illuminate\Database\Eloquent\Model;

class RecordVersionService
{
    public function recordUpdate(Model $model, array $oldValues, array $newValues, array $metadata = []): ?RecordVersion
    {
        $changedFields = $this->changedFields($oldValues, $newValues);

        if ($changedFields === []) {
            return null;
        }

        $user = auth()->user();

        return RecordVersion::create([
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
            'metadata' => $metadata,
        ]);
    }

    public function snapshot(Model $model, array $relations = []): array
    {
        if ($relations !== []) {
            $model->loadMissing($relations);
        }

        return $this->normalize($model->toArray());
    }

    private function changedFields(array $oldValues, array $newValues, string $prefix = ''): array
    {
        $fields = [];
        $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($keys as $key) {
            $old = $oldValues[$key] ?? null;
            $new = $newValues[$key] ?? null;
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($old) && is_array($new) && $this->isAssoc($old) && $this->isAssoc($new)) {
                array_push($fields, ...$this->changedFields($old, $new, $path));
                continue;
            }

            if ($old !== $new) {
                $fields[] = $path;
            }
        }

        return $fields;
    }

    private function normalize(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $values[$key] = $value->value;
            } elseif (is_array($value)) {
                $values[$key] = $this->normalize($value);
            }
        }

        ksort($values);

        return $values;
    }

    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }
}
