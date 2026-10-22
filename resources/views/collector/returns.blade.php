<x-role-layout title="Collecte et traçabilité">
    <div class="mx-auto max-w-7xl">
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Espace collecteur</p>
        <h1 class="mt-2 text-3xl font-black">Dépôts à traiter</h1>
        <p class="mt-2 text-slate-500">Contrôlez le QR, le poids et l’état. Chaque action et chaque preuve sont historisées.</p>

        @if($errors->any())<div role="alert" class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if($tradeIns->isNotEmpty())
            <section class="mt-8"><h2 class="text-2xl font-black">Reprises à réceptionner</h2><div class="mt-4 space-y-4">@foreach($tradeIns as $tradeIn)<article class="panel p-6"><div class="flex justify-between"><div><p class="font-mono text-xs text-slate-400">{{ $tradeIn->reference }}</p><h3 class="font-black">{{ $tradeIn->itemName() }}</h3><p class="text-sm text-slate-500">{{ $tradeIn->consumer->name }} · {{ $tradeIn->collectionPoint->name }}</p></div><x-module.badge>{{ $tradeIn->statusLabel() }}</x-module.badge></div><form method="POST" enctype="multipart/form-data" action="{{ route('collector.trade-ins.receive',$tradeIn) }}" class="mt-4 grid gap-4 md:grid-cols-3" x-data="qrScanner()" @submit="stop()">@csrf<div><label class="text-sm font-black">QR de reprise</label><input x-ref="token" name="qr_token" class="field" required><button type="button" @click="start()" class="mt-2 text-sm font-black text-sky-700">Scanner avec la caméra</button><p x-text="error" class="mt-1 text-xs text-rose-600"></p></div><div x-show="scanning" x-cloak><video x-ref="video" class="h-36 w-full rounded-xl bg-black object-cover"></video><button type="button" @click="stop()" class="text-xs text-rose-600">Arrêter</button></div><div><label class="text-sm font-black">Photo de réception</label><input type="file" name="evidence" accept="image/jpeg,image/png,image/webp" required class="mt-2 block w-full"></div><div class="md:col-span-3"><button class="btn-primary">Confirmer la reprise et valider les points</button></div></form></article>@endforeach</div></section>
        @endif
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

                    @php($reserved=$item->materialMatches->firstWhere('status','accepted'))
                    @if($item->status === 'reserved_by_brand' && $reserved && $item->logistics_mode === 'hub_transfer')
                        <div class="mt-5 rounded-xl bg-sky-50 p-4"><h3 class="font-bold">Remettre la matière à {{ $reserved->request->brand->name }}</h3><p class="mt-2 text-sm">{{ $reserved->request->dropoff_address ?: 'Remise organisée avec la marque' }}</p><p class="mt-2 break-all font-mono text-xs">Code attendu : {{ $reserved->transfer_token }}</p>
                        <form method="POST" enctype="multipart/form-data" action="{{ route('collector.material-transfers.store',$reserved) }}" class="mt-4 grid gap-3 md:grid-cols-3">@csrf
                            <x-ui.input name="transfer_token" label="Code de remise" required/><x-ui.input name="note" label="Transporteur, destinataire et date" required/>
                            <label class="text-sm font-bold">Photo de remise<input type="file" name="evidence" accept="image/jpeg,image/png,image/webp" required class="mt-2 block w-full"></label>
                            <button class="rounded-xl bg-sky-700 px-4 py-3 font-bold text-white">Confirmer la remise</button>
                        </form></div>
                    @elseif(in_array($item->status,['marketplace','in_transit']))<p class="mt-5 text-sm text-slate-500">{{ $item->status === 'marketplace' ? 'Matière vérifiée : en attente d’une réservation par une marque.' : 'Remise enregistrée : en attente de la réception par la marque.' }}</p>@endif
                    @if(in_array($item->status,['dropoff_expected','deposit_declared','received','inspected','routed']))
                    <form method="POST" action="{{ route('collector.returns.update',$item) }}" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-3" x-data="qrScanner()" @submit="stop()">
                        @csrf @method('PATCH')
                        <x-ui.select name="status" label="Nouvelle étape" :options="collect([
                            'received'=>'Reçu au point','inspected'=>'Contrôlé','routed'=>'Orienté','completed'=>'Terminé','rejected'=>'Refusé'
                        ])" required/>
                        <div><label class="text-sm font-black">Code QR (réception)</label><input x-ref="token" name="qr_token" class="field"><button type="button" @click="start()" class="mt-2 text-sm font-black text-sky-700">Scanner avec la caméra</button><p x-text="error" class="mt-1 text-xs text-rose-600"></p></div><div x-show="scanning" x-cloak><video x-ref="video" class="h-36 w-full rounded-xl bg-black object-cover"></video><button type="button" @click="stop()" class="text-xs text-rose-600">Arrêter</button></div>
                        <x-ui.input name="confirmed_weight_kg" label="Poids confirmé (kg)" type="number" step="0.01" :value="$item->confirmed_weight_kg"/>
                        <x-ui.select name="condition" label="État constaté" :value="$item->condition" :options="collect(['good'=>'Bon','wearable'=>'Portable','minor_damage'=>'Petits défauts','damaged'=>'Endommagé','unusable'=>'Inutilisable','unknown'=>'Inconnu'])"/>
                        <div><label class="text-sm font-black">Preuve photo</label><input type="file" name="evidence" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-slate-200 p-3 text-sm"></div>
                        <x-ui.input name="note" label="Note de traçabilité"/>
                        <div class="md:col-span-3"><button class="rounded-xl bg-emerald-700 px-5 py-3 font-black text-white">Enregistrer l’étape</button></div>
                    </form>
                    @endif
                </article>
            @empty
                <x-ui.empty message="Aucun dépôt à traiter pour vos points de collecte."/>
            @endforelse
        </div>
        <div class="mt-6">{{ $items->links() }}</div>
    </div>
</x-role-layout>

