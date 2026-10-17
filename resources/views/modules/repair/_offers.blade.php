{{-- Liste des offres d'une annonce. Variables : $listing, $respondRoute (nom de route), $soldRoute --}}
<div class="space-y-3">
    @forelse($listing->offers as $offer)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
            <div>
                <p class="font-black">{{ number_format((float) $offer->offered_price, 2, ',', ' ') }} {{ $listing->currency }} <span class="text-sm font-semibold text-slate-500">· {{ $offer->buyer->name }}</span></p>
                @if($offer->message)<p class="text-sm text-slate-500">« {{ $offer->message }} »</p>@endif
                <p class="text-xs text-slate-400">{{ $offer->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-2">
                <x-module.badge :tone="['accepted' => 'good', 'declined' => 'bad', 'pending' => 'warn'][$offer->status] ?? 'neutral'">{{ $offer->statusLabel() }}</x-module.badge>
                @if($offer->status === 'pending' && $listing->status === 'published')
                    <form method="POST" action="{{ route($respondRoute, $offer) }}">@csrf @method('PATCH')<button name="decision" value="accept" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-500">Accepter</button></form>
                    <form method="POST" action="{{ route($respondRoute, $offer) }}">@csrf @method('PATCH')<button name="decision" value="decline" class="rounded-lg px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Refuser</button></form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-500">Aucune offre pour le moment.</p>
    @endforelse
    @if($listing->status === 'reserved')
        <form method="POST" action="{{ route($soldRoute, $listing) }}">@csrf<x-module.button module="repair" class="w-full">Confirmer la vente</x-module.button></form>
    @endif
</div>
