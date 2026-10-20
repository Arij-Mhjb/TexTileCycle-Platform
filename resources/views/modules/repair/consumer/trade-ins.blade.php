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
                <article class="consumer-card flex flex-wrap items-center justify-between gap-4 p-5">
                    <div><p class="text-[13px] font-semibold text-amber-600">{{ $tradeIn->reference }}</p><h2 class="mt-1 font-black">{{ $tradeIn->itemName() }}</h2><p class="text-sm text-slate-500">{{ \App\Models\TradeInRequest::CONDITIONS[$tradeIn->condition] ?? $tradeIn->condition }}@if($tradeIn->evaluation_note) · {{ $tradeIn->evaluation_note }}@endif</p></div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($tradeIn->proposed_points)<span class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-black text-amber-800">{{ $tradeIn->proposed_points }} pts</span>@endif
                        <x-module.badge :tone="['completed' => 'good', 'declined' => 'bad'][$tradeIn->status] ?? 'repair'">{{ $tradeIn->statusLabel() }}</x-module.badge>
                        @if($tradeIn->status === 'evaluated')
                            <form method="POST" action="{{ route('consumer.trade-ins.accept', $tradeIn) }}">@csrf<x-module.button module="repair" class="!py-1.5">Accepter</x-module.button></form>
                            <form method="POST" action="{{ route('consumer.trade-ins.decline', $tradeIn) }}">@csrf<button class="rounded-xl px-3 py-1.5 text-sm font-bold text-rose-600 hover:bg-rose-50">Refuser</button></form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="consumer-card"><x-ui.empty message="Aucune demande de reprise."/></div>
            @endforelse
        </div>
    </div>
</x-consumer-layout>
