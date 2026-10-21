<x-consumer-layout title="Récompenses">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">Utiliser mes points</p><h1 class="mt-2 text-3xl font-black">Catalogue de récompenses</h1><p class="mt-2 text-slate-500">Solde disponible : <strong class="text-emerald-700">{{ $balance }} points</strong></p></div><a href="{{ route('consumer.wallet') }}" class="btn-secondary">Historique du wallet</a></div>
    <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($items as $item)
            <article class="consumer-card p-6"><p class="text-xs font-black uppercase tracking-wider text-emerald-600">{{ $item->partner_name ?: 'TexTileCycle' }}</p><h2 class="mt-2 text-xl font-black">{{ $item->name }}</h2><p class="mt-3 text-sm text-slate-500">{{ $item->description }}</p><div class="mt-5 flex items-center justify-between"><strong class="text-2xl text-emerald-700">{{ $item->points_cost }} pts</strong><form method="POST" action="{{ route('consumer.rewards.redeem',$item) }}" onsubmit="return confirm('Échanger {{ $item->points_cost }} points ?')">@csrf<button class="btn-primary" @disabled($balance < $item->points_cost)>Échanger</button></form></div></article>
        @empty <div class="md:col-span-2 xl:col-span-3"><x-ui.empty message="Aucune récompense disponible pour le moment."/></div> @endforelse
    </div>
    <section class="consumer-card mt-8 overflow-hidden"><div class="border-b p-6"><h2 class="text-xl font-black">Mes codes</h2></div><div class="divide-y">
        @forelse($redemptions as $redemption)<article class="flex flex-wrap items-center justify-between gap-4 p-5"><div><p class="font-black">{{ $redemption->item->name }}</p><p class="text-xs text-slate-400">{{ $redemption->reference }} · {{ $redemption->status === 'used' ? 'utilisée le '.optional($redemption->used_at)->format('d/m/Y') : 'expire le '.optional($redemption->expires_at)->format('d/m/Y') }}</p></div><div class="rounded-xl px-4 py-3 font-mono font-black tracking-widest text-white {{ $redemption->status === 'used' ? 'bg-slate-400 line-through' : 'bg-slate-950' }}">{{ $redemption->code }}</div></article>
        @empty <div class="p-6"><x-ui.empty message="Aucun échange réalisé."/></div> @endforelse
    </div><div class="p-4">{{ $redemptions->links() }}</div></section>
</x-consumer-layout>
