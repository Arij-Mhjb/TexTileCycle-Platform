@php($display = $post->original ?? $post)
<article id="post-{{ $display->id }}" class="consumer-card overflow-visible p-5 sm:p-6">
    @if($post->repost_of_id)<p class="mb-4 text-xs font-black text-emerald-700">↻ {{ $post->author->name }} a republié</p>@endif
    <div class="flex items-start gap-3">
        <a href="{{ route('consumer.community.profile', $display->author) }}" class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl bg-emerald-100 font-black text-emerald-700">@if($display->author->profile_photo_url)<img src="{{ $display->author->profile_photo_url }}" alt="" class="h-full w-full object-cover">@else{{ strtoupper(substr($display->author->name, 0, 1)) }}@endif</a>
        <div class="min-w-0 flex-1"><a href="{{ route('consumer.community.profile', $display->author) }}" class="font-black hover:text-emerald-700">{{ $display->author->name }}</a><p class="text-xs text-slate-400">{{ $display->created_at->diffForHumans() }}</p></div>
        @if($post->user_id === auth()->id())<form method="POST" action="{{ route('consumer.community.posts.destroy', $post) }}" data-confirm="Supprimer cette publication ?">@csrf @method('DELETE')<button class="rounded-lg px-2 py-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="Supprimer">•••</button></form>@endif
    </div>
    @if($display->body)<div class="mt-4 whitespace-normal text-[15px] leading-7 text-slate-700 dark:text-slate-200">{!! $display->formattedBody() !!}</div>@endif
    @if($display->media_url)<img src="{{ $display->media_url }}" alt="Image de la publication" class="mt-4 max-h-[34rem] w-full rounded-2xl object-cover" loading="lazy">@endif

    <div class="mt-5 flex items-center gap-2 border-y border-slate-100 py-2 dark:border-slate-800">
        <form method="POST" action="{{ route('consumer.community.posts.like', $display) }}">@csrf<button class="rounded-xl px-3 py-2 text-sm font-black transition {{ $display->liked_by_me ? 'bg-rose-50 text-rose-600' : 'text-slate-500 hover:bg-slate-50' }}">{{ $display->liked_by_me ? '♥' : '♡' }} {{ $display->likes_count }}</button></form>
        <button type="button" onclick="document.getElementById('comment-{{ $display->id }}').focus()" class="rounded-xl px-3 py-2 text-sm font-black text-slate-500 hover:bg-slate-50">💬 {{ $display->comments_count }}</button>
        @if($display->user_id !== auth()->id())<form method="POST" action="{{ route('consumer.community.posts.repost', $display) }}" data-confirm="Republier ce contenu dans votre fil ?">@csrf<button class="rounded-xl px-3 py-2 text-sm font-black text-slate-500 hover:bg-emerald-50 hover:text-emerald-700">↻ {{ $display->reposts_count }}</button></form>@endif
    </div>

    @if($display->comments->isNotEmpty())<div class="mt-4 space-y-3">@foreach($display->comments->take(5) as $comment)<div class="flex gap-2"><span class="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-xl bg-slate-100 text-xs font-black">@if($comment->author->profile_photo_url)<img src="{{ $comment->author->profile_photo_url }}" alt="" class="h-full w-full object-cover">@else{{ strtoupper(substr($comment->author->name,0,1)) }}@endif</span><div class="min-w-0 flex-1 rounded-2xl bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800"><p class="font-black">{{ $comment->author->name }}</p><div>{!! $comment->formattedBody() !!}</div></div>@if($comment->user_id === auth()->id())<form method="POST" action="{{ route('consumer.community.comments.destroy',$comment) }}" data-confirm="Supprimer ce commentaire ?">@csrf @method('DELETE')<button class="text-xs text-rose-500">×</button></form>@endif</div>@endforeach</div>@endif

    <form method="POST" action="{{ route('consumer.community.comments.store', $display) }}" class="relative mt-4 flex gap-2" x-data="mentionComposer(@js(route('consumer.community.users.search')))" @click.outside="close()">
        @csrf
        <template x-for="member in mentions" :key="member.id"><input type="hidden" name="mentions[]" :value="member.id"></template>
        <input id="comment-{{ $display->id }}" name="body" x-ref="field" @input.debounce.250ms="search()" maxlength="1200" required class="field !mt-0" placeholder="Ajouter un commentaire… Utilisez @ pour mentionner">
        <button class="btn-primary !px-4">Envoyer</button>
        <div x-cloak x-show="open" class="absolute bottom-full left-0 z-30 mb-2 w-full overflow-hidden rounded-2xl border bg-white shadow-xl"><template x-for="member in results" :key="member.id"><button type="button" @click="select(member)" class="block w-full px-4 py-2 text-left text-sm font-bold hover:bg-emerald-50" x-text="'@' + member.name"></button></template></div>
    </form>
</article>
