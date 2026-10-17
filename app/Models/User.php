<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'branch'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch',
    ];

    public function getBranchName(): ?string
    {
        if (! empty($this->branch)) {
            return $this->branch;
        }
        if (strcasecmp($this->name, 'pila') === 0) {
            return 'Pila';
        }
        if (stripos($this->name, 'santa cruz') !== false) {
            return 'Santa Cruz';
        }
        return null;
    }

    public function isMasterAdmin(): bool
    {
        return $this->role === 'super_admin' || ($this->role === 'admin' && empty($this->branch) && strcasecmp($this->name, 'pila') !== 0 && stripos($this->name, 'santa cruz') === false);
    }

    public static function getMasterAdmin(): ?self
    {
        return static::where('role', 'super_admin')->first()
            ?? static::where('email', 'admin@gmail.com')->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function riderProfile()
    {
        return $this->hasOne(RiderProfile::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
