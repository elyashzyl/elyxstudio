<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'gender', 'bio'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $appends = ['avatar', 'avatar_url'];

    protected function avatar(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->profile_photo_updated_at) {
                return null;
            }

            return route('profile.avatar', ['user' => $this->getKey()], false)
                .'?v='.$this->profile_photo_updated_at->timestamp;
        });
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'profile_photo_updated_at' => 'datetime',
        ];
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isCommerceAdmin(): bool
    {
        return $this->isSuperadmin();
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function profilePhoto(): HasOne
    {
        return $this->hasOne(UserProfilePhoto::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function approvedProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'approved_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
