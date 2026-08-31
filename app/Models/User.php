<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Sitewyn\Core\Base\Models\Concerns\HasPermissions;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Models\UserMeta;

#[Fillable(['name', 'username', 'email', 'avatar', 'password', 'is_super_admin', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPermissions, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Public URL of the stored avatar (relative storage path, `avatars/…` on
     * the public disk); null when the account has no avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar
            ? Storage::disk('public')->url($this->avatar)
            : null);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * @return HasMany<UserMeta, $this>
     */
    public function meta(): HasMany
    {
        return $this->hasMany(UserMeta::class);
    }

    /**
     * Team members manage the platform itself: super admins and anyone holding
     * the built-in Admin role (slug `admin`, seeded by SuperAdminSeeder). Team
     * accounts are managed at /admin/system/users (SystemUserController);
     * every other account belongs to /admin/users (UserController).
     */
    public function isTeamMember(): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $this->loadMissing('roles');

        return $this->roles->contains('slug', 'admin');
    }
}
