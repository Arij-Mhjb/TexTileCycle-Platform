<?php
namespace App\Policies;
use App\Models\Evidence; use App\Models\User;
class EvidencePolicy {
 public function before(User $user): ?bool { return $user->isAdmin()?true:null; }
 public function create(User $user, Evidence $evidence): bool { return $user->isBrand() && $evidence->assessment?->user_id===$user->id; }
 public function update(User $user, Evidence $evidence): bool { return $this->create($user,$evidence); }
}
