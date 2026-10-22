<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassportEvent extends Model
{
    protected $fillable = [
        'passport_proposal_id', 'actor_id', 'actor_role', 'event', 'from_status',
        'to_status', 'note', 'metadata',
    ];

    protected function casts(): array { return ['metadata' => 'array']; }
    public function proposal(): BelongsTo { return $this->belongsTo(PassportProposal::class, 'passport_proposal_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
