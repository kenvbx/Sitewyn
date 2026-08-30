<?php

namespace Sitewyn\Core\Base\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'version', 'description', 'is_active', 'activated_at'])]
class Plugin extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }
}
