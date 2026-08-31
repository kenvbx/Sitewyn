<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'properties', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    // Audit entries are append-only, so only created_at is managed.
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }
}
