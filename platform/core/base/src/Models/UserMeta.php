<?php

namespace Sitewyn\Core\Base\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sitewyn\Core\Base\Database\Factories\UserMetaFactory;

#[Fillable(['user_id', 'key', 'value'])]
class UserMeta extends Model
{
    /** @use HasFactory<UserMetaFactory> */
    use HasFactory;

    protected $table = 'user_meta';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): UserMetaFactory
    {
        return UserMetaFactory::new();
    }
}
