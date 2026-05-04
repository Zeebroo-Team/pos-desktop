<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Account\Models\Account;
use Modules\AppConnection\Models\UserAppConnection;
use Modules\Business\Models\Business;
use Modules\HRManagement\Models\Employee;
use Modules\Settings\Concerns\HasSettings;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasSettings, Notifiable;

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
        ];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Third-party app connections (OAuth) for this user.
     *
     * @return HasMany<UserAppConnection, $this>
     */
    public function appConnections(): HasMany
    {
        return $this->hasMany(UserAppConnection::class);
    }

    /** Linked HR employee profile (self-service portal), if any. */
    public function hrEmployee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Employee-only account: has a linked HR employee row and does not own any business (admins always get full app access).
     */
    public function isHrPortalOnlyUser(): bool
    {
        if ($this->hasRole('admin')) {
            return false;
        }

        if (! $this->hrEmployee()->exists()) {
            return false;
        }

        return ! $this->businesses()->exists();
    }
}
