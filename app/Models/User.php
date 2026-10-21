<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function productReturns(): HasMany
    {
        return $this->hasMany(ProductReturn::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class);
    }

    public function brandMembership(): HasOne
    {
        return $this->hasOne(BrandMember::class);
    }

    public function brandNotifications(): HasMany
    {
        return $this->hasMany(BrandNotification::class);
    }

    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }

    public function consumerNotifications(): HasMany
    {
        return $this->hasMany(ConsumerNotification::class);
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function consumerClaims(): HasMany
    {
        return $this->hasMany(ConsumerClaim::class);
    }

    public function marketplaceOrders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'buyer_id');
    }

    public function repairOrders(): HasMany
    {
        return $this->hasMany(RepairOrder::class);
    }

    public function tradeInRequests(): HasMany
    {
        return $this->hasMany(TradeInRequest::class);
    }

    public function resaleOffers(): HasMany
    {
        return $this->hasMany(ResaleOffer::class);
    }

    public function passportProposals(): HasMany
    {
        return $this->hasMany(PassportProposal::class, 'consumer_id');
    }

    public function operatedCollectionPoints(): HasMany
    {
        return $this->hasMany(CollectionPoint::class, 'operator_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isConsumer(): bool
    {
        return $this->role === 'consumer';
    }

    public function isBrand(): bool
    {
        return $this->role === 'brand';
    }

    public function isBrandMember(): bool
    {
        return $this->role === 'brand_member';
    }

    public function isCollector(): bool
    {
        return $this->role === 'collector';
    }
}

