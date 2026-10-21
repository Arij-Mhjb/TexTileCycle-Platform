<x-consumer-layout title="Reprise contre points">
    <x-module.hero module="repair" eyebrow="Échange" title="Reprise contre points" description="Proposez un vêtement que vous ne portez plus : TexTileCycle l’évalue et vous propose des points à utiliser dans votre wallet."/>
    @include('modules.repair.consumer._tabs')
    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('consumer.trade-ins.store') }}" class="consumer-card h-fit space-y-4 p-6" x-data="{ known: true }">
            @csrf
            <h2 class="text-lg font-black">Nouvelle reprise</h2>
            <div class="flex gap-2 text-xs font-bold"><button type="button" @click="known = true" :class="known ? 'bg-amber-600 text-white' : 'bg-slate-100'" class="rounded-full px-3 py-1.5">Article du catalogue</button><button type="button" @click="known = false" :class="!known ? 'bg-amber-600 text-white' : 'bg-slate-100'" class="rounded-full px-3 py-1.5">Autre article</button></div>
            <div x-show="known"><x-ui.select name="product_id" label="Article" :options="$products->mapWithKeys(fn ($product) => [$product->id => $product->name.' · '.$product->brand])"/></div>
            <div x-show="!known" x-cloak><x-module.field name="product_name" label="Nom de l’article" placeholder="Veste en jean"/></div>
            <x-ui.select name="condition" label="État" :options="\App\Models\TradeInRequest::CONDITIONS" required/>
            <x-ui.textarea name="description" label="Description" rows="3"/>
            <x-module.button module="repair" class="w-full">Demander une offre</x-module.button>
        </form>
        <div class="space-y-4 lg:col-span-2">
            @forelse($tradeIns as $tradeIn)
                <article class="consumer-card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                    <div><p class="text-[13px] font-semibold text-amber-600">{{ $tradeIn->reference }}</p><h2 class="mt-1 font-black">{{ $tradeIn->itemName() }}</h2><p class="text-sm text-slate-500">{{ \App\Models\TradeInRequest::CONDITIONS[$tradeIn->condition] ?? $tradeIn->condition }}@if($tradeIn->evaluation_note) · {{ $tradeIn->evaluation_note }}@endif</p></div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($tradeIn->proposed_points)<span class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-black text-amber-800">{{ $tradeIn->proposed_points }} pts</span>@endif
                        <x-module.badge :tone="['completed' => 'good', 'declined' => 'bad'][$tradeIn->status] ?? 'repair'">{{ $tradeIn->statusLabel() }}</x-module.badge>
                        @if($tradeIn->status === 'evaluated')
                            <form method="POST" action="{{ route('consumer.trade-ins.accept', $tradeIn) }}" data-confirm="Accepter l’offre de {{ $tradeIn->proposed_points }} points et choisir ce point de reprise ?" class="flex gap-2">@csrf<select name="collection_point_id" class="field !mt-0" required><option value="">Point de reprise</option>@foreach($points as $point)<option value="{{ $point->id }}">{{ $point->name }} · {{ $point->city }}</option>@endforeach</select><x-module.button module="repair" class="!py-1.5">Accepter</x-module.button></form>
                            <form method="POST" action="{{ route('consumer.trade-ins.decline', $tradeIn) }}" data-confirm="Refuser définitivement cette offre de reprise ?">@csrf<button class="rounded-xl px-3 py-1.5 text-sm font-bold text-rose-600 hover:bg-rose-50">Refuser</button></form>
                        @endif
                    </div>
                    </div>
                    @if(in_array($tradeIn->status,['accepted','deposited','completed']) && $tradeIn->qr_token)
                        <div class="mt-4 grid gap-4 rounded-2xl bg-slate-950 p-5 text-white sm:grid-cols-[auto_1fr]"><canvas data-qr-value="{{ $tradeIn->qr_token }}" class="rounded-xl bg-white p-2"></canvas><div><p class="text-xs uppercase tracking-wider text-amber-300">QR de reprise</p><p class="mt-2 font-black">{{ $tradeIn->collectionPoint?->name }}</p><p class="text-sm text-slate-300">{{ $tradeIn->collectionPoint?->address }}, {{ $tradeIn->collectionPoint?->city }}</p><p class="mt-2 break-all font-mono text-xs">{{ $tradeIn->qr_token }}</p>@if($tradeIn->status==='accepted')<form method="POST" enctype="multipart/form-data" action="{{ route('consumer.trade-ins.deposit',$tradeIn) }}" data-confirm="Déclarer que l’article a été déposé dans ce point de reprise ?" class="mt-3">@csrf<input type="file" name="evidence" accept="image/jpeg,image/png,image/webp" data-max-bytes="3072000" data-validation-message="Choisissez une image JPG, PNG ou WebP de 3 Mo maximum." required class="text-xs"><button class="mt-2 rounded-xl bg-amber-500 px-4 py-2 font-bold text-slate-950">Déclarer mon dépôt</button></form>@elseif($tradeIn->status==='deposited')<p class="mt-3 text-sm font-bold text-amber-300">Dépôt déclaré · confirmation du collecteur attendue.</p>@else<p class="mt-3 text-sm font-bold text-emerald-300">Reprise terminée · points validés.</p>@endif</div></div>
                    @endif
                </article>
            @empty
                <div class="consumer-card"><x-ui.empty message="Aucune demande de reprise."/></div>
            @endforelse
        </div>
    </div>
</x-consumer-layout>
