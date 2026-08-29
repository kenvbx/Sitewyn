<?php

namespace Sitewyn\Packages\Media\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['folder_id', 'name', 'file_name', 'path', 'disk', 'mime_type', 'size', 'width', 'height', 'conversions', 'alt_text'])]
class MediaFile extends Model
{
    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'conversions' => 'array',
        ];
    }
}
