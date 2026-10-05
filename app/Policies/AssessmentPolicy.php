<?php
namespace App\Policies;
use App\Models\Assessment; use App\Models\User;
class AssessmentPolicy {
 public function before(User $user): ?bool { return $user->isAdmin()?true:null; }
 public function view(User $user, Assessment $assessment): bool { return $user->isBrand() && $assessment->user_id===$user->id; }
 public function update(User $user, Assessment $assessment): bool { return $this->view($user,$assessment); }
}
