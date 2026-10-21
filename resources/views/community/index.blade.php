<x-consumer-layout title="Communauté circulaire">
    <div class="grid gap-7 lg:grid-cols-[minmax(0,1fr)_19rem]">
        <div>
            <section class="consumer-card overflow-visible p-5 sm:p-6">
                <div class="flex gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl bg-emerald-600 font-black text-white">
                        @if(auth()->user()->profile_photo_url)<img src="{{ auth()->user()->profile_photo_url }}" alt="" class="h-full w-full object-cover">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif
                    </span>
                    <form method="POST" action="{{ route('consumer.community.posts.store') }}" enctype="multipart/form-data" class="min-w-0 flex-1 space-y-3" x-data="mentionComposer(@js(route('consumer.community.users.search')))" @click.outside="close()">
                        @csrf
                        <template x-for="member in mentions" :key="member.id"><input type="hidden" name="mentions[]" :value="member.id"></template>
                        <div class="relative">
                            <textarea name="body" x-ref="field" @input.debounce.250ms="search()" rows="3" maxlength="3000" class="field !mt-0 resize-none" placeholder="Partagez une idée, une tenue ou une action circulaire… Utilisez @ pour mentionner un membre."></textarea>
                            <div x-cloak x-show="open" class="absolute inset-x-0 top-full z-30 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                                <template x-for="member in results" :key="member.id"><button type="button" @click="select(member)" class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-slate-800"><span class="grid h-9 w-9 place-items-center overflow-hidden rounded-xl bg-emerald-100 font-black text-emerald-700"><img x-show="member.avatar" :src="member.avatar" class="h-full w-full object-cover"><span x-show="!member.avatar" x-text="member.name.charAt(0).toUpperCase()"></span></span><span class="font-bold" x-text="member.name"></span></button></template>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="cursor-pointer rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-200"><input type="file" name="media" accept="image/jpeg,image/png,image/webp" data-max-bytes="5242880" data-validation-message="Choisissez une image JPG, PNG ou WebP de 5 Mo maximum." class="sr-only">📷 Ajouter une image</label>
                            <button class="btn-primary">Publier</button>
                        </div>
                    </form>
                </div>
            </section>

            <div class="mt-6 space-y-5">
                @forelse($posts as $post)
                    @include('community._post', ['post' => $post])
                @empty
                    <div class="consumer-card"><x-ui.empty message="Soyez la première personne à publier dans la communauté."/></div>
                @endforelse
            </div>
            <div class="mt-7">{{ $posts->links() }}</div>
        </div>

        <aside class="h-fit space-y-4 lg:sticky lg:top-28">
            <div class="consumer-card p-5"><p class="text-xs font-black uppercase tracking-widest text-emerald-600">Communauté</p><h1 class="mt-2 text-2xl font-black">Le fil circulaire</h1><p class="mt-3 text-sm leading-6 text-slate-500">Inspirez les autres membres, partagez vos réparations et donnez une nouvelle vie aux vêtements.</p></div>
            <div class="rounded-3xl bg-slate-950 p-5 text-white"><p class="font-black">Bonnes pratiques</p><ul class="mt-3 space-y-2 text-sm text-slate-300"><li>• Mentionnez avec @</li><li>• Respectez les autres membres</li><li>• Ne publiez aucune donnée sensible</li></ul></div>
        </aside>
    </div>
</x-consumer-layout>
