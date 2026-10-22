<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotQueryLog extends Model
{
    protected $fillable = [
        'question_text',
        'matched_question_id',
        'session_id',
    ];

    public function matchedQuestion(): BelongsTo
    {
        return $this->belongsTo(ChatbotQuestion::class, 'matched_question_id');
    }
}
