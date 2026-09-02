<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['url', 'method', 'status_code', 'ip_address', 'user_agent'])]
class RequestLog extends Model
{
    public const UPDATED_AT = null;
}
