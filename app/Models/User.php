<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array { return ['email_verified_at' => 'datetime', 'password' => 'hashed']; }

    public function scans(): HasMany { return $this->hasMany(Scan::class); }
    public function productReturns(): HasMany { return $this->hasMany(ProductReturn::class); }
    public function assessments(): HasMany { return $this->hasMany(Assessment::class); }
    public function brand(): HasOne { return $this->hasOne(Brand::class); }
    public function brandNotifications(): HasMany { return $this->hasMany(BrandNotification::class); }
    public function rewardTransactions(): HasMany { return $this->hasMany(RewardTransaction::class); }
    public function consumerNotifications(): HasMany { return $this->hasMany(ConsumerNotification::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isConsumer(): bool { return $this->role === 'consumer'; }
    public function isBrand(): bool { return $this->role === 'brand'; }
}
