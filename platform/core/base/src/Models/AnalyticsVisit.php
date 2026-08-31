<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['path', 'referer', 'user_agent', 'ip', 'session_id'])]
class AnalyticsVisit extends Model
{
    // Visits are append-only analytics facts: only created_at is managed.
    public const UPDATED_AT = null;
}
