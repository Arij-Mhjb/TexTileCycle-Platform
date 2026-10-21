<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommunityPost extends Model
{
    protected $fillable = ['user_id', 'body', 'media_path', 'repost_of_id'];

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function original(): BelongsTo { return $this->belongsTo(self::class, 'repost_of_id'); }
    public function comments(): HasMany { return $this->hasMany(CommunityComment::class); }
    public function likes(): HasMany { return $this->hasMany(CommunityPostLike::class); }
    public function reposts(): HasMany { return $this->hasMany(self::class, 'repost_of_id'); }
    public function mentions(): BelongsToMany { return $this->belongsToMany(User::class, 'community_post_mentions'); }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }

    public function formattedBody(): HtmlString
    {
        $safe = e($this->body ?? '');
        $linked = $this->mentions->sortByDesc(fn (User $user) => mb_strlen($user->name))->reduce(function (string $text, User $user): string {
            $name = e($user->name);
            return preg_replace_callback('/@'.preg_quote($name, '/').'(?=\s|[.,!?;:]|$)/u', fn (): string => '<a class="font-black text-emerald-700 hover:underline" href="'.e(route('consumer.community.profile', $user)).'">@'.$name.'</a>', $text);
        }, $safe);

        return new HtmlString(Str::of($linked)->replace("\n", '<br>')->toString());
    }
}
