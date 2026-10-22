<x-consumer-layout title="Réclamations">
    <div><p class="eyebrow">Assistance et litiges</p><h1 class="mt-2 text-3xl font-black">Mes réclamations</h1><p class="mt-2 text-slate-500">Contestez un poids, un refus, une récompense ou une opération de seconde vie.</p></div>
    <div class="mt-8 grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
        <form method="POST" action="{{ route('consumer.claims.store') }}" class="consumer-card h-fit space-y-4 p-6">@csrf
            <h2 class="text-xl font-black">Nouvelle réclamation</h2>
            <x-ui.select name="category" label="Catégorie" :options="\App\Models\ConsumerClaim::CATEGORIES" required/>
            <x-ui.input name="subject" label="Objet" required/>
            <x-ui.select name="product_return_id" label="Retour concerné (facultatif)" :options="$returns->mapWithKeys(fn($r)=>[$r->id=>$r->reference.' · '.($r->product?->name ?? 'Article')])"/>
            <x-ui.select name="repair_order_id" label="Réparation concernée (facultatif)" :options="$repairs->mapWithKeys(fn($r)=>[$r->id=>$r->reference])"/>
            <x-ui.select name="trade_in_request_id" label="Reprise concernée (facultatif)" :options="$tradeIns->mapWithKeys(fn($r)=>[$r->id=>$r->reference])"/>
            <x-ui.textarea name="description" label="Description détaillée" rows="6" required/>
            <button class="btn-primary w-full">Envoyer la réclamation</button>
        </form>
        <div class="space-y-4">@forelse($claims as $claim)<article class="consumer-card p-6"><div class="flex flex-wrap justify-between gap-3"><div><p class="font-mono text-xs text-slate-400">{{ $claim->reference }}</p><h2 class="mt-1 font-black">{{ $claim->subject }}</h2></div><x-module.badge :tone="$claim->status==='resolved'?'good':($claim->status==='rejected'?'bad':'warning')">{{ $claim->statusLabel() }}</x-module.badge></div><p class="mt-3 text-sm text-slate-600">{{ $claim->description }}</p>@if($claim->admin_response)<div class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm"><strong>Réponse TexTileCycle</strong><p class="mt-1">{{ $claim->admin_response }}</p></div>@endif</article>@empty<x-ui.empty message="Aucune réclamation."/>@endforelse<div>{{ $claims->links() }}</div></div>
    </div>
</x-consumer-layout>
