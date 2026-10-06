<x-role-layout title="Suivi du retour">
    <div class="mx-auto max-w-5xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('product-returns.index') }}" class="text-sm font-black text-emerald-600">← Mes retours</a>
                <p class="mt-4 text-xs font-black uppercase tracking-[.2em] text-emerald-600">Retour {{ $item->reference }}</p>
                <h1 class="mt-2 text-3xl font-black">{{ $item->product->name }}</h1>
                <p class="mt-2 text-slate-500">{{ $item->collectionPoint->name }} · {{ $item->collectionPoint->city }}</p>
            </div>
            <span class="self-start rounded-full bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">{{ $item->statusLabel() }}</span>
        </div>

        <section class="mt-8 grid gap-5 lg:grid-cols-[.9fr_1.1fr]">
            <article class="panel p-6">
                <h2 class="text-xl font-black">Code de dépôt</h2>
                <div class="mt-5 rounded-2xl bg-slate-950 p-6 text-center text-white">
                    <p class="text-xs font-bold uppercase tracking-[.25em] text-emerald-300">À présenter au partenaire</p>
                    <p class="mt-4 break-all font-mono text-lg font-black">{{ $item->qr_token }}</p>
                    <p class="mt-3 text-sm text-slate-400">{{ $item->reference }}</p>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Action</dt><dd class="font-black">{{ str($item->action)->replace('_',' ')->headline() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Poids estimé</dt><dd class="font-black">{{ $item->weight_kg !== null ? $item->weight_kg.' kg' : 'Non renseigné' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Poids confirmé</dt><dd class="font-black">{{ $item->confirmed_weight_kg !== null ? $item->confirmed_weight_kg.' kg' : 'En attente' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-400">Récompense</dt><dd class="font-black">{{ $item->rewardTransaction?->points ?? 0 }} pts · {{ $item->rewardTransaction?->status === 'validated' ? 'validés' : 'en attente' }}</dd></div>
                </dl>
                @if ($item->canConsumerEdit() || auth()->user()->isAdmin())
                    <a href="{{ route('product-returns.edit', $item) }}" class="mt-6 inline-flex rounded-xl border border-slate-200 px-4 py-2 text-sm font-black">Modifier</a>
                @endif
            </article>

            <article class="panel p-6">
                <h2 class="text-xl font-black">Traçabilité</h2>
                <p class="mt-1 text-sm text-slate-500">Chaque étape confirmée est conservée dans l’historique.</p>
                <div class="mt-6 space-y-0">
                    @forelse ($item->statusHistory as $history)
                        <div class="relative flex gap-4 pb-7 last:pb-0">
                            @if (! $loop->last)
                                <span class="absolute left-[15px] top-8 h-[calc(100%-1rem)] w-px bg-emerald-200"></span>
                            @endif
                            <span class="relative mt-1 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-600 text-sm font-black text-white">✓</span>
                            <div>
                                <p class="font-black">{{ str($history->status)->replace('_',' ')->headline() }}</p>
                                <p class="text-xs text-slate-400">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->user?->name ?? 'Système' }}</p>
                                @if ($history->note)<p class="mt-2 text-sm text-slate-600">{{ $history->note }}</p>@endif
                            </div>
                        </div>
                    @empty
                        <x-ui.empty message="L’historique sera alimenté à la prochaine mise à jour."/>
                    @endforelse
                </div>
            </article>
        </section>

        @if ($item->treatmentResult)
            <section class="panel mt-6 p-6">
                <h2 class="text-xl font-black">Résultat du traitement</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl bg-emerald-50 p-5"><p class="text-3xl font-black text-emerald-700">{{ $item->treatmentResult->recycled_percent }}%</p><p class="text-sm font-bold">Recyclé</p></div>
                    <div class="rounded-2xl bg-sky-50 p-5"><p class="text-3xl font-black text-sky-700">{{ $item->treatmentResult->reused_percent }}%</p><p class="text-sm font-bold">Réemployé</p></div>
                    <div class="rounded-2xl bg-slate-100 p-5"><p class="text-3xl font-black">{{ $item->treatmentResult->waste_percent }}%</p><p class="text-sm font-bold">Résiduel</p></div>
                </div>
            </section>
        @endif
    </div>
</x-role-layout>
