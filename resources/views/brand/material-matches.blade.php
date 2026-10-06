<x-brand-layout>
    <div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Matching vérifié</p><h1 class="mt-2 text-3xl font-black">Retours compatibles</h1><p class="mt-2 text-slate-500">Les coordonnées du consommateur restent masquées. La traçabilité passe par le QR de transfert.</p></div>

    <div class="mt-7 space-y-5">
        @forelse ($matches as $match)
            @php($return = $match->productReturn)
            <article class="rounded-3xl border bg-white p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div><p class="text-xs font-black uppercase text-emerald-600">{{ $match->request->title }}</p><h2 class="mt-2 text-2xl font-black">{{ $return->product->name }}</h2><p class="text-sm text-slate-500">{{ $return->reference }} · score {{ $match->match_score }} % · {{ $return->confirmed_weight_kg ?? $return->weight_kg }} kg</p></div>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-black">{{ str($match->status)->replace('_',' ')->headline() }}</span>
                </div>
                <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_.8fr]">
                    <div>
                        <div class="grid grid-cols-3 gap-2">@foreach(collect($return->scan?->assets ?? [])->where('type','!=','receipt') as $asset)<img src="{{ $asset->url }}" class="h-28 w-full rounded-xl object-cover">@endforeach</div>
                        <p class="mt-4 text-sm"><strong>État vérifié :</strong> {{ $return->condition }} · <strong>Admin :</strong> {{ $return->admin_review_note ?: 'Conforme' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-5">
                        @if($match->status === 'suggested')
                            <form method="POST" action="{{ route('brand.material-matches.accept',$match) }}">@csrf<button class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Accepter et réserver</button></form>
                        @elseif(in_array($match->status,['accepted','in_transit']))
                            <p class="text-sm"><strong>QR transfert :</strong><br><span class="break-all font-mono">{{ $match->transfer_token }}</span></p>
                            <form method="POST" action="{{ route('brand.material-matches.receive',$match) }}" class="mt-4">@csrf<button class="w-full rounded-xl bg-sky-600 px-4 py-3 font-black text-white">Confirmer la réception</button></form>
                        @elseif($match->status === 'brand_received')
                            <form method="POST" action="{{ route('brand.material-matches.verify',$match) }}" class="space-y-3">@csrf<textarea name="note" placeholder="Résultat du contrôle final" class="w-full rounded-xl border-slate-300"></textarea><button class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Valider la matière et les points</button></form>
                            <form method="POST" action="{{ route('brand.material-matches.reject',$match) }}" class="mt-3 space-y-3">@csrf<textarea name="note" required placeholder="Motif du refus" class="w-full rounded-xl border-slate-300"></textarea><button class="w-full rounded-xl bg-rose-100 px-4 py-3 font-black text-rose-700">Refuser</button></form>
                        @else
                            <p class="text-sm text-slate-500">{{ $match->brand_review_note ?: 'Aucune action requise.' }}</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <x-ui.empty message="Aucune matière compatible pour le moment."/>
        @endforelse
    </div>
    <div class="mt-8">{{ $matches->links() }}</div>
</x-brand-layout>
