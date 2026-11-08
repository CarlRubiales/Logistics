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
        $nameOrEmail = strtolower(($this->name ?? '') . ' ' . ($this->email ?? ''));
        foreach (\App\Services\LagunaHubProvisioner::municipalities() as $muni) {
            if (str_contains($nameOrEmail, $muni['slug']) || str_contains($nameOrEmail, strtolower($muni['branch']))) {
                return $muni['branch'];
            }
        }
        if (str_contains($nameOrEmail, 'pila')) {
            return 'Pila';
        }
        if (str_contains($nameOrEmail, 'santa cruz') || str_contains($nameOrEmail, 'stacruz') || str_contains($nameOrEmail, 'sta cruz') || str_contains($nameOrEmail, 'cruz')) {
            return 'Santa Cruz';
        }
        if (str_contains($nameOrEmail, 'victoria')) {
            return 'Victoria';
        }
        if (str_contains($nameOrEmail, 'los banos') || str_contains($nameOrEmail, 'losbanos')) {
            return 'Los Banos';
        }
        if (str_contains($nameOrEmail, 'main') || str_contains($nameOrEmail, 'central') || str_contains($nameOrEmail, 'hq')) {
            return 'Main';
        }
        // Match against existing active hubs
        $hubs = Hub::where('is_active', true)->get();
        foreach ($hubs as $hub) {
            if ($hub->branch && str_contains($nameOrEmail, strtolower($hub->branch))) {
                return $hub->branch;
            }
            if ($hub->name && str_contains($nameOrEmail, strtolower($hub->name))) {
                return $hub->branch ?: $hub->name;
            }
        }
        return $hubs->first()?->branch ?: 'Pila';
    }

    public function getAssignedBranch(): ?string
    {
        return $this->isMasterAdmin() ? null : $this->getBranchName();
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
