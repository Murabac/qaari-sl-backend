<?php

namespace App\Models;

use App\Enums\StaffRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([
            StaffRole::SuperAdmin->value,
            StaffRole::Admin->value,
            StaffRole::Production->value,
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(StaffRole::SuperAdmin->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(StaffRole::Admin->value);
    }

    public function isProduction(): bool
    {
        return $this->hasRole(StaffRole::Production->value);
    }

    public function isReviewer(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteRecitations(): BelongsToMany
    {
        return $this->belongsToMany(Recitation::class, 'favorites')->withTimestamps();
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    public function createdReciters(): HasMany
    {
        return $this->hasMany(Reciter::class, 'created_by');
    }

    public function createdRecitations(): HasMany
    {
        return $this->hasMany(Recitation::class, 'created_by');
    }
}
