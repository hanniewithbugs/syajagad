<?php

namespace App\Models;

use App\Models\Invoice;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nis', 'name', 'gender', 'tgl_lahir', 'alamat', 'role', 'santri_status', 'admin_permissions', 'email', 'username', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function hasAdminPermission(string $permission): bool
    {
        if ($this->role !== 'admin') {
            return false;
        }

        if ($this->admin_permissions === null) {
            return true;
        }

        return in_array($permission, $this->admin_permissions, true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tgl_lahir' => 'date',
            'admin_permissions' => 'array',
            'password' => 'hashed',
        ];
    }
}
