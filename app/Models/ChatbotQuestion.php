<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatbotQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatbot_category_id',
        'question',
        'keywords',
        'answer',
        'is_active',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ChatbotCategory::class, 'chatbot_category_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ChatbotQueryLog::class, 'matched_question_id');
    }

    protected const STOPWORDS = [
        'comment', 'pourquoi', 'quand', 'quel', 'quels', 'quelle', 'quelles', 'quoi', 'que', 'qui',
        'les', 'des', 'une', 'un', 'le', 'la', 'de', 'du', 'et', 'ou', 'est', 'sur', 'dans', 'avec',
        'pour', 'par', 'mon', 'ma', 'mes', 'ton', 'ta', 'tes', 'son', 'sa', 'ses', 'vous', 'votre',
        'nous', 'notre', 'puis', 'ce', 'cette', 'ces', 'faire', 'fais', 'dois', 'peut', 'peux', 'etre',
    ];

    public static function findBestMatch(string $message): ?self
    {
        $tokens = array_filter(
            explode(' ', static::normalize($message)),
            fn ($token) => mb_strlen($token) >= 3 && ! in_array($token, self::STOPWORDS, true)
        );

        if ($tokens === []) {
            return null;
        }

        return static::query()->where('is_active', true)->get()
            ->map(function (self $question) use ($tokens) {
                $haystack = static::normalize($question->question.' '.$question->keywords);
                $score = 0;

                foreach ($tokens as $token) {
                    if (str_contains($haystack, $token)) {
                        $score++;
                    }
                }

                return [$question, $score];
            })
            ->filter(fn ($pair) => $pair[1] > 0)
            ->sortByDesc(fn ($pair) => $pair[1])
            ->first()[0] ?? null;
    }

    protected static function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii($value));

        return trim(preg_replace('/[^a-z0-9\s]/', ' ', $value));
    }
}
