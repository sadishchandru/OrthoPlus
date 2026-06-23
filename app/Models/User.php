<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'page_access',
        'module',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'page_access' => 'array',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /** Accepts a role name or array of names. */
    public function hasRole($roles): bool
    {
        $roles = is_array($roles) ? $roles : func_get_args();
        if (in_array('root', $this->roles->pluck('name')->all(), true)) {
            return true; // root has every role
        }
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    public function roleNames(): array
    {
        return $this->roles->pluck('name')->all();
    }

    /**
     * Effective page access. Root role => ['*'] (everything). Else, a per-user
     * override (page_access column) if set, otherwise the union of the user's
     * roles' page_access.
     */
    public function pageAccess(): array
    {
        $this->loadMissing('roles');
        if ($this->roles->pluck('name')->contains('root')) {
            return ['*'];
        }
        if (!empty($this->page_access)) {
            return array_values($this->page_access);
        }
        return $this->roles
            ->flatMap(fn($r) => $r->page_access ?? [])
            ->unique()->values()->all();
    }
}
