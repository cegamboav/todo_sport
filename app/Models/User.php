<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function eventStaffAssignments(): HasMany
    {
        return $this->hasMany(EventStaff::class);
    }

    public function professor(): HasOne
    {
        return $this->hasOne(Professor::class);
    }

    public function referee(): HasOne
    {
        return $this->hasOne(Referee::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function isProfessor(): bool
    {
        return $this->role === UserRole::Professor;
    }

    public function isMesa(): bool
    {
        return $this->role === UserRole::Mesa;
    }

    public function isCorner(): bool
    {
        return $this->role === UserRole::Corner;
    }
}
