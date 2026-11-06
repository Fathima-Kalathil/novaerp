<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Writes an audit_logs row whenever the model is created, updated or deleted.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->writeAudit('created'));
        static::updated(fn ($model) => $model->writeAudit('updated'));
        static::deleted(fn ($model) => $model->writeAudit('deleted'));
    }

    protected function writeAudit(string $action): void
    {
        $ignore = ['updated_at', 'created_at', 'stock_on_hand'];

        $old = null;
        $new = null;

        if ($action === 'created') {
            $new = array_diff_key($this->getAttributes(), array_flip($ignore));
        } elseif ($action === 'updated') {
            $new = array_diff_key($this->getChanges(), array_flip($ignore));
            if (empty($new)) {
                return;
            }
            $old = array_intersect_key($this->getOriginal(), $new);
        } else {
            $old = array_diff_key($this->getOriginal(), array_flip($ignore));
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => class_basename($this),
            'auditable_id' => $this->getKey(),
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
