<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CommunityComment extends Model
{
    protected $fillable = ['community_post_id', 'user_id', 'body'];

    public function post(): BelongsTo { return $this->belongsTo(CommunityPost::class, 'community_post_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function mentions(): BelongsToMany { return $this->belongsToMany(User::class, 'community_comment_mentions'); }

    public function formattedBody(): HtmlString
    {
        $safe = e($this->body);
        $linked = $this->mentions->sortByDesc(fn (User $user) => mb_strlen($user->name))->reduce(function (string $text, User $user): string {
            $name = e($user->name);
            return preg_replace_callback('/@'.preg_quote($name, '/').'(?=\s|[.,!?;:]|$)/u', fn (): string => '<a class="font-black text-emerald-700 hover:underline" href="'.e(route('consumer.community.profile', $user)).'">@'.$name.'</a>', $text);
        }, $safe);

        return new HtmlString(Str::of($linked)->replace("\n", '<br>')->toString());
    }
}
