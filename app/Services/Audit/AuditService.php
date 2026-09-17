<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;

class AuditService
{
    public static function log(
        string $action,
        ?string $model = null,
        ?int $modelId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?User $user = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
