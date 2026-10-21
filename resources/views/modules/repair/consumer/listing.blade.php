<x-consumer-layout :title="$listing->title">
    @include('modules.repair.consumer._tabs')
    <div class="grid gap-8 lg:grid-cols-2">
        <div><div class="overflow-hidden rounded-2xl bg-amber-50"><img src="{{ $listing->image_url }}" alt="{{ $listing->title }}" class="h-full max-h-[32rem] w-full object-cover"></div>
            @if($listing->canTryOn())<button type="button" data-try-on-open class="mt-4 w-full rounded-2xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-3.5 font-black text-white shadow-lg shadow-violet-200 transition hover:-translate-y-0.5">✦ Essayer ce vêtement avec l’IA</button>@endif
        </div>
        <div>
            <x-module.badge tone="repair">{{ $listing->gradeLabel() }} · Grade {{ $listing->condition_grade }}</x-module.badge>
            <h1 class="mt-3 text-3xl font-black tracking-tight">{{ $listing->title }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $listing->brand?->name ?? 'TexTileCycle' }} · {{ $listing->statusLabel() }}</p>
            <p class="mt-5 text-4xl font-black text-amber-700">{{ number_format((float) $listing->price, 2, ',', ' ') }} <span class="text-lg">{{ $listing->currency }}</span></p>
            @if($listing->points_price)<p class="mt-1 text-xl font-black text-emerald-700">ou {{ $listing->points_price }} points <span class="text-sm text-slate-500">· solde {{ $pointsBalance }}</span></p>@endif
            @if($listing->description)<p class="mt-5 leading-7 text-slate-600">{{ $listing->description }}</p>@endif

            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/60 p-5 dark:border-amber-900 dark:bg-amber-950/20">
                <p class="text-[13px] font-semibold text-amber-700">Passeport seconde vie</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @if($listing->product?->materials->isNotEmpty())<li><strong>Composition :</strong> {{ $listing->product->materials->map(fn ($material) => $material->name.($material->pivot->percentage ? ' '.(float) $material->pivot->percentage.' %' : ''))->join(', ') }}</li>@endif
                    @if($listing->repairOrder)<li><strong>Origine :</strong> retour consommateur reconditionné ({{ $listing->repairOrder->reference }})</li>@endif
                    @foreach($listing->repairOrder?->refurbishments ?? [] as $refurbishment)<li><strong>Atelier :</strong> {{ $refurbishment->actions }}</li>@endforeach
                </ul>
            </div>

            @if($listing->isFromConsumer(auth()->user()))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="font-semibold text-emerald-800 dark:text-emerald-300">C’est votre article</p>
                    <p class="mt-1 text-emerald-700 dark:text-emerald-400">Vous l’avez retourné ({{ $listing->repairOrder->reference }}) et il a été reconditionné. Vous avez reçu {{ \App\Services\RepairMarketplaceService::RESALE_POINTS }} points pour sa seconde vie. Les autres membres peuvent maintenant faire une offre.</p>
                    <a href="{{ route('consumer.repairs.index') }}" class="mt-3 inline-block font-semibold text-emerald-800 underline dark:text-emerald-300">Suivre dans Mes réparations</a>
                </div>
            @else
            @if($listing->status === 'published' && $listing->points_price)
                <form method="POST" action="{{ route('consumer.marketplace.buy-with-points', $listing) }}" data-confirm="Utiliser {{ $listing->points_price }} points pour acheter cet article ?" class="mt-6 space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5" x-data="{ fulfilment: 'pickup' }">
                    @csrf
                    <div class="flex items-center justify-between gap-3"><div><h2 class="font-black text-emerald-900">Acheter avec mes points</h2><p class="text-sm text-emerald-700">Débit sécurisé après confirmation.</p></div><strong class="text-xl text-emerald-800">{{ $listing->points_price }} pts</strong></div>
                    <label class="block"><span class="text-sm font-bold">Mode de remise</span><select name="fulfilment_method" x-model="fulfilment" class="field" required><option value="pickup">Retrait en point partenaire</option><option value="delivery">Livraison</option></select></label>
                    <div x-show="fulfilment === 'pickup'"><x-ui.select name="collection_point_id" label="Point de retrait" :options="$pickupPoints->mapWithKeys(fn($p)=>[$p->id=>$p->name.' · '.$p->city])" x-bind:required="fulfilment === 'pickup'"/></div>
                    <div x-show="fulfilment === 'delivery'" x-cloak><x-ui.textarea name="delivery_address" label="Adresse de livraison" x-bind:required="fulfilment === 'delivery'"/></div>
                    <button class="btn-primary w-full" @disabled($pointsBalance < $listing->points_price)>{{ $pointsBalance < $listing->points_price ? 'Solde insuffisant' : 'Acheter avec mes points' }}</button>
                </form>
            @endif
            @if($myOffer)
                <div class="mt-6 rounded-2xl bg-slate-100 p-4 text-sm dark:bg-slate-800">Votre offre : <strong>{{ number_format((float) $myOffer->offered_price, 2, ',', ' ') }} {{ $listing->currency }}</strong> — {{ $myOffer->statusLabel() }}</div>
            @endif
            @if($myOrder && $myOrder->status === 'awaiting_checkout')
                <form method="POST" action="{{ route('consumer.marketplace.checkout',$myOrder) }}" data-confirm="Confirmer cette commande de {{ number_format((float) $myOrder->total_amount, 2, ',', ' ') }} {{ $listing->currency }} ?" class="mt-6 space-y-3 rounded-2xl border border-amber-200 bg-amber-50 p-5" x-data="{ fulfilment: 'pickup' }">
                    @csrf
                    <h2 class="font-black">Finaliser la commande {{ $myOrder->reference }}</h2>
                    <x-ui.select name="fulfilment_method" label="Remise" :options="collect(['pickup'=>'Retrait en point partenaire','delivery'=>'Livraison'])" x-model="fulfilment" required/>
                    <div x-show="fulfilment === 'pickup'"><x-ui.select name="collection_point_id" label="Point de retrait" :options="$pickupPoints->mapWithKeys(fn($p)=>[$p->id=>$p->name.' · '.$p->city])" x-bind:required="fulfilment === 'pickup'"/></div>
                    <div x-show="fulfilment === 'delivery'" x-cloak><x-ui.textarea name="delivery_address" label="Adresse de livraison" x-bind:required="fulfilment === 'delivery'"/></div>
                    <input type="hidden" name="payment_method" :value="fulfilment === 'pickup' ? 'pay_on_pickup' : 'cash_on_delivery'">
                    <p class="text-sm text-amber-800" x-text="fulfilment === 'pickup' ? 'Paiement lors du retrait.' : 'Paiement à la livraison.'"></p>
                    <button class="btn-primary w-full">Confirmer la commande</button>
                </form>
            @elseif($myOrder)
                <div class="mt-6 rounded-2xl bg-sky-50 p-5"><p class="font-black">Commande {{ $myOrder->reference }} · {{ $myOrder->statusLabel() }}</p>@if(in_array($myOrder->status,['ready','shipped']))<form method="POST" action="{{ route('consumer.marketplace.received',$myOrder) }}" data-confirm="Confirmer que vous avez bien reçu cet article ?" class="mt-3">@csrf<button class="btn-primary">Confirmer la réception</button></form>@endif</div>
            @endif
            @if($listing->status === 'published' && (! $myOffer || $myOffer->status !== 'pending'))
                <form method="POST" action="{{ route('consumer.marketplace.offer', $listing) }}" class="mt-6 grid gap-3 sm:grid-cols-3">
                    @csrf
                    <x-module.field name="offered_price" type="number" step="0.01" min="1" label="Votre offre (TND)" :value="(float) $listing->price" required/>
                    <div class="sm:col-span-2"><x-module.field name="message" label="Message (facultatif)" placeholder="Disponible pour un retrait samedi…"/></div>
                    <div class="sm:col-span-3"><x-module.button module="repair" class="w-full">Envoyer mon offre</x-module.button></div>
                </form>
            @endif
            @endif
        </div>
    </div>
    @if($listing->canTryOn())
        @include('modules.repair.consumer._virtual-try-on', ['listing' => $listing])
    @endif
</x-consumer-layout>
