<x-consumer-layout title="Mon wallet">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">Récompenses vérifiées</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight">Mon wallet circulaire</h1>
            <p class="mt-2 text-slate-500">Les points deviennent disponibles uniquement après validation du dépôt et du traitement.</p>
        </div>
        <div class="flex gap-2"><a href="{{ route('consumer.rewards.index') }}" class="btn-secondary">Utiliser mes points</a><a href="{{ route('product-returns.create') }}" class="rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">Déclarer un retour</a></div>
    </div>

    <section class="mt-8 grid gap-4 md:grid-cols-3">
        <article class="consumer-card p-6">
            <p class="text-sm font-bold text-slate-400">Solde disponible</p>
            <p class="mt-3 text-4xl font-black text-emerald-600">{{ $balance }} pts</p>
        </article>
        <article class="consumer-card p-6">
            <p class="text-sm font-bold text-slate-400">En attente de validation</p>
            <p class="mt-3 text-4xl font-black text-amber-500">{{ $pending }} pts</p>
        </article>
        <article class="consumer-card p-6">
            <p class="text-sm font-bold text-slate-400">Total gagné</p>
            <p class="mt-3 text-4xl font-black">{{ $earned }} pts</p>
        </article>
    </section>

    <section class="consumer-card mt-8 overflow-hidden">
        <div class="border-b border-slate-100 p-6 dark:border-slate-800">
            <h2 class="text-xl font-black">Historique des transactions</h2>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($transactions as $transaction)
                <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-black">{{ $transaction->description }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-xl font-black {{ $transaction->points >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $transaction->points >= 0 ? '+' : '' }}{{ $transaction->points }} pts
                        </p>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            {{ ['pending'=>'En attente','validated'=>'Validé','cancelled'=>'Annulé','spent'=>'Utilisé','expired'=>'Expiré'][$transaction->status] ?? $transaction->status }}
                        </span>
                    </div>
                </article>
            @empty
                <div class="p-8"><x-ui.empty message="Aucune transaction pour le moment."/></div>
            @endforelse
        </div>
    </section>

    <div class="mt-8">{{ $transactions->links() }}</div>
</x-consumer-layout>
