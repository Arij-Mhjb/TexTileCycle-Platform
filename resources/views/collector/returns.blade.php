<x-role-layout title="Collecte et traçabilité">
    <div class="mx-auto max-w-7xl">
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Espace collecteur</p>
        <h1 class="mt-2 text-3xl font-black">Dépôts à traiter</h1>
        <p class="mt-2 text-slate-500">Contrôlez le QR, le poids et l’état. Chaque action et chaque preuve sont historisées.</p>

        <div class="mt-8 space-y-5">
            @forelse($items as $item)
                <article class="panel p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div><p class="font-mono text-xs text-slate-400">{{ $item->reference }}</p><h2 class="mt-1 text-xl font-black">{{ $item->product->name }}</h2><p class="text-sm text-slate-500">{{ $item->user->name }} · {{ $item->collectionPoint->name }}</p></div>
                        <span class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">{{ $item->statusLabel() }}</span>
                    </div>

                    @if($item->evidences->isNotEmpty())
                        <div class="mt-5 flex gap-3 overflow-x-auto">@foreach($item->evidences as $evidence)<a href="{{ $evidence->url }}" target="_blank" rel="noopener" class="shrink-0"><img src="{{ $evidence->url }}" alt="Preuve {{ $evidence->type }}" class="h-24 w-24 rounded-xl object-cover"><span class="mt-1 block max-w-24 truncate text-xs text-slate-400">{{ str($evidence->type)->replace('_',' ') }}</span></a>@endforeach</div>
                    @endif

                    <form method="POST" action="{{ route('collector.returns.update',$item) }}" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-3">
                        @csrf @method('PATCH')
                        <x-ui.select name="status" label="Nouvelle étape" :options="collect([
                            'received'=>'Reçu au point','inspected'=>'Contrôlé','routed'=>'Orienté','completed'=>'Terminé','rejected'=>'Refusé'
                        ])" required/>
                        <x-ui.input name="qr_token" label="Code QR (réception)"/>
                        <x-ui.input name="confirmed_weight_kg" label="Poids confirmé (kg)" type="number" step="0.01" :value="$item->confirmed_weight_kg"/>
                        <x-ui.select name="condition" label="État constaté" :value="$item->condition" :options="collect(['good'=>'Bon','wearable'=>'Portable','minor_damage'=>'Petits défauts','damaged'=>'Endommagé','unusable'=>'Inutilisable','unknown'=>'Inconnu'])"/>
                        <div><label class="text-sm font-black">Preuve photo</label><input type="file" name="evidence" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-slate-200 p-3 text-sm"></div>
                        <x-ui.input name="note" label="Note de traçabilité"/>
                        <div class="md:col-span-3"><button class="rounded-xl bg-emerald-700 px-5 py-3 font-black text-white">Enregistrer l’étape</button></div>
                    </form>
                </article>
            @empty
                <x-ui.empty message="Aucun dépôt à traiter pour vos points de collecte."/>
            @endforelse
        </div>
        <div class="mt-6">{{ $items->links() }}</div>
    </div>
</x-role-layout>
