<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user): ?bool { return $user->isAdmin() ? true : null; }
    public function viewAny(User $user): bool { return $user->isBrand(); }
    public function create(User $user): bool { return $user->isBrand(); }
    public function update(User $user, Product $product): bool { return $user->isBrand() && $user->brand?->id === $product->brand_id; }
    public function delete(User $user, Product $product): bool { return $this->update($user,$product); }
}
