<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    /** Record an important action, e.g. Audit::log('property.approve', $property). */
    public static function log(string $action, ?Model $model = null, array $old = [], array $new = []): void
    {
        (new AuditLog)->forceFill([
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => $model?->getTable(),
            'record_id' => $model?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()->ip(),
        ])->save();
    }
}
