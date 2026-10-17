<x-app-layout title="Reprises">
    <x-module.hero module="repair" title="Reprises (trade-in)" description="Les consommateurs proposent leurs vêtements en échange de points. Évaluez, puis validez à la réception.">
        <x-slot:stats>
            <x-module.stat label="À évaluer" :value="$counts['submitted'] ?? 0"/>
            <x-module.stat label="Offres envoyées" :value="$counts['evaluated'] ?? 0"/>
            <x-module.stat label="À réceptionner" :value="$counts['accepted'] ?? 0"/>
            <x-module.stat label="Terminées" :value="$counts['completed'] ?? 0"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.repair.admin._tabs')
    <div class="space-y-4">
        @forelse($tradeIns as $tradeIn)
            <article class="panel flex flex-wrap items-center justify-between gap-4 p-5">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-wider text-amber-600">{{ $tradeIn->reference }} · {{ \App\Models\TradeInRequest::CONDITIONS[$tradeIn->condition] ?? $tradeIn->condition }}</p>
                    <h2 class="mt-1 font-black">{{ $tradeIn->itemName() }} <span class="text-sm font-semibold text-slate-500">· {{ $tradeIn->consumer->name }}</span></h2>
                    @if($tradeIn->description)<p class="text-sm text-slate-500">{{ $tradeIn->description }}</p>@endif
                    @if($tradeIn->proposed_points)<p class="mt-1 text-xs text-slate-500">Offre : <strong>{{ $tradeIn->proposed_points }} points</strong> @if($tradeIn->evaluation_note)· {{ $tradeIn->evaluation_note }}@endif</p>@endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-module.badge :tone="['completed' => 'good', 'declined' => 'bad', 'submitted' => 'warn'][$tradeIn->status] ?? 'repair'">{{ $tradeIn->statusLabel() }}</x-module.badge>
                    @if(in_array($tradeIn->status, ['submitted', 'evaluated'], true))
                        <form method="POST" action="{{ route('admin.trade-ins.evaluate', $tradeIn) }}" class="flex flex-wrap gap-2">
                            @csrf @method('PATCH')
                            <input type="number" name="proposed_points" min="1" value="{{ $tradeIn->proposed_points ?? 40 }}" class="field !mt-0 w-24 !py-1.5" aria-label="Points proposés">
                            <input name="evaluation_note" placeholder="Note" class="field !mt-0 w-40 !py-1.5">
                            <x-module.button module="repair" class="!py-1.5">{{ $tradeIn->status === 'evaluated' ? 'Modifier l’offre' : 'Proposer' }}</x-module.button>
                        </form>
                    @elseif($tradeIn->status === 'accepted')
                        <form method="POST" action="{{ route('admin.trade-ins.complete', $tradeIn) }}">@csrf<x-module.button module="repair" class="!py-1.5">Article reçu → créditer</x-module.button></form>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel"><x-ui.empty message="Aucune demande de reprise."/></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $tradeIns->links() }}</div>
</x-app-layout>
