<x-consumer-layout title="Mes réparations">
    <x-module.hero module="repair" eyebrow="Réparer plutôt que jeter" title="Mes réparations" :description="'Faites réparer un article retourné. S’il est remis en vente après reconditionnement, vous gagnez '.\App\Services\RepairMarketplaceService::RESALE_POINTS.' points.'"/>
    @include('modules.repair.consumer._tabs')

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- L'analyse IA prend quelques secondes : le bouton est bloqué après le premier clic pour éviter les doublons. --}}
        <form method="POST" action="{{ route('consumer.repairs.store') }}" enctype="multipart/form-data" class="consumer-card h-fit space-y-4 p-6"
              x-data="{ sending: false }" @submit="if (sending) { $event.preventDefault(); } sending = true">
            @csrf
            <h2 class="text-lg font-black">Demander une réparation</h2>
            @if($returns->isEmpty())
                <p class="text-sm text-slate-500">Déclarez d’abord un retour pour pouvoir demander une réparation.</p>
                <a href="{{ route('product-returns.create') }}" class="btn-secondary w-full">Déclarer un retour</a>
            @else
                <x-ui.select name="product_return_id" label="Article retourné" :options="$returns->mapWithKeys(fn ($return) => [$return->id => ($return->product?->name ?? 'Article').' · '.$return->reference])" required/>
                <x-ui.textarea name="issue_description" label="Problème constaté" rows="4" required/>
                <x-ui.select name="repair_center_id" label="Centre souhaité (facultatif)" :options="$centers->mapWithKeys(fn($c)=>[$c->id=>$c->name.' · '.$c->city])"/>
                <x-ui.input name="preferred_appointment_at" type="datetime-local" label="Créneau souhaité (facultatif)"/>
                <label class="block rounded-2xl border border-dashed border-violet-300 bg-violet-50/60 p-4">
                    <span class="text-sm font-bold text-violet-800">✦ Photo pour le pré-diagnostic IA (facultatif)</span>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-white file:px-2 file:py-1.5 file:text-xs file:font-bold file:text-violet-700">
                    @error('photo')<span class="mt-1 block text-sm font-semibold text-rose-600">{{ $message }}</span>@enderror
                </label>
                <x-module.button module="repair" class="w-full disabled:cursor-wait disabled:opacity-60" x-bind:disabled="sending"><span x-text="sending ? 'Analyse IA en cours…' : 'Envoyer la demande'">Envoyer la demande</span></x-module.button>
            @endif
        </form>
        <div class="space-y-4 lg:col-span-2">
            @forelse($orders as $order)
                <article class="consumer-card p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="text-[13px] font-semibold text-amber-600">{{ $order->reference }}</p><h2 class="mt-1 text-lg font-black">{{ $order->product?->name ?? 'Article' }}</h2><p class="text-sm text-slate-500">{{ $order->decisionLabel() ?? 'En attente de diagnostic' }}@if($order->repairCenter) · {{ $order->repairCenter->name }}@endif</p></div>
                        @include('modules.repair._status', ['order' => $order])
                    </div>
                    <div class="mt-5">@include('modules.repair._timeline', ['order' => $order])</div>
                    @if($order->ai_diagnosis)<div class="mt-4">@include('modules.repair._ai-diagnosis', ['order' => $order])</div>@endif
                    @if($order->status === 'diagnosed' && $order->consumer_decision === 'pending')
                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4"><p class="font-black">Votre accord est requis</p><p class="mt-1 text-sm">{{ $order->decisionLabel() }} · estimation {{ number_format((float)$order->estimated_cost,2,',',' ') }} TND</p><div class="mt-3 flex gap-2"><form method="POST" action="{{ route('consumer.repairs.respond',$order) }}">@csrf<input type="hidden" name="decision" value="accept"><button class="btn-primary">Accepter</button></form><form method="POST" action="{{ route('consumer.repairs.respond',$order) }}">@csrf<input type="hidden" name="decision" value="decline"><button class="btn-danger">Refuser</button></form></div></div>
                    @elseif($order->consumer_decision === 'accepted')<p class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm font-bold text-emerald-800">Proposition acceptée par vous le {{ optional($order->consumer_decided_at)->format('d/m/Y H:i') }}.</p>@endif
                    @if($order->reward_points)<p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm font-bold text-amber-800">+{{ $order->reward_points }} points gagnés</p>@endif
                    @if($order->listing)<a href="{{ route('consumer.marketplace.show', $order->listing) }}" class="mt-3 inline-block text-sm font-bold text-amber-700 underline">Voir l’annonce sur le marketplace</a>@endif
                </article>
            @empty
                <div class="consumer-card"><x-ui.empty message="Aucune réparation en cours."/></div>
            @endforelse
        </div>
    </div>
</x-consumer-layout>
