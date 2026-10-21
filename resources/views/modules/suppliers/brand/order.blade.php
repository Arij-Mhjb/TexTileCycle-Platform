{{-- Détail d'une commande fournisseur côté marque : lignes, contre-proposition, livraisons, réception, journal. --}}
@php $fmt = fn (float $kg) => rtrim(rtrim(number_format($kg, 2, ',', ' '), '0'), ','); @endphp
<x-brand-layout>
    <x-module.hero module="suppliers" :eyebrow="'Bon de commande · '.$order->supplier->name" :title="$order->reference"
        :description="'Commandée le '.$order->ordered_at->format('d/m/Y').($order->expected_at ? ' · livraison attendue le '.$order->expected_at->format('d/m/Y') : '').'. '.$order->statusLabel().'.'">
        <x-slot:actions>
            <x-module.button module="suppliers" variant="light" :href="route('brand.suppliers.show', $order->supplier)">← Fiche fournisseur</x-module.button>
        </x-slot:actions>
        <x-slot:stats>
            <x-module.stat label="Statut" :value="$order->shortStatusLabel()"/>
            <x-module.stat label="Expédié" :value="$order->shippedPercent().' %'" :hint="$fmt($order->shippedKg()).' / '.$fmt($order->dueKg()).' kg'"/>
            <x-module.stat label="Réceptionné" :value="$order->receivedPercent().' %'" :hint="$fmt($order->receivedKg()).' kg'"/>
            <x-module.stat label="Montant" :value="$order->total_amount ? number_format((float) $order->total_amount, 0, ',', ' ').' '.$order->currency : '—'"/>
        </x-slot:stats>
    </x-module.hero>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if($order->isLate())
                <div class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900"><strong>Livraison en retard.</strong> La date convenue ({{ $order->expected_at->format('d/m/Y') }}) est dépassée et {{ $fmt($order->dueKg() - $order->shippedKg()) }} kg restent à expédier.</div>
            @endif

            {{-- Contre-proposition à arbitrer --}}
            @if($order->awaitsBrandDecision())
                <section class="rounded-2xl border-2 border-purple-300 bg-white p-6" x-data="{ reject: {{ $errors->has('reason') ? 'true' : 'false' }} }">
                    <p class="text-[13px] font-semibold text-purple-600">À décider</p>
                    <h2 class="mt-1 text-xl font-black">{{ $order->supplier->name }} propose des modifications</h2>
                    <ul class="mt-3 space-y-1 text-sm text-slate-700">
                        @foreach($order->lines as $line)
                            @if($line->confirmed_kg !== null && abs((float) $line->confirmed_kg - (float) $line->quantity_kg) >= 0.01)
                                <li>{{ $line->material?->name }} : <strong>{{ $fmt((float) $line->confirmed_kg) }} kg</strong> au lieu de {{ $fmt((float) $line->quantity_kg) }} kg</li>
                            @endif
                        @endforeach
                        @if($order->proposed_expected_at)<li>Livraison le <strong>{{ $order->proposed_expected_at->format('d/m/Y') }}</strong>@if($order->expected_at) au lieu du {{ $order->expected_at->format('d/m/Y') }}@endif</li>@endif
                    </ul>
                    @if($order->supplier_note)<p class="mt-3 rounded-xl bg-purple-50 px-4 py-3 text-sm italic text-purple-900">« {{ $order->supplier_note }} »</p>@endif
                    @if($order->linesAmount() !== null)<p class="mt-3 text-sm text-slate-600">Nouveau montant si vous acceptez : <strong>{{ number_format($order->linesAmount(), 2, ',', ' ') }} {{ $order->currency }}</strong>@if($order->total_amount) (au lieu de {{ number_format((float) $order->total_amount, 2, ',', ' ') }})@endif</p>@endif
                    <div class="mt-5 flex flex-wrap gap-3" x-show="!reject">
                        <form method="POST" action="{{ route('brand.purchase-orders.counter', $order) }}">@csrf<input type="hidden" name="decision" value="accept"><x-module.button module="suppliers">Accepter la proposition</x-module.button></form>
                        <button type="button" @click="reject = true" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 hover:bg-rose-50">Refuser et annuler la commande</button>
                    </div>
                    <form method="POST" action="{{ route('brand.purchase-orders.counter', $order) }}" x-cloak x-show="reject" class="mt-5 space-y-3">
                        @csrf<input type="hidden" name="decision" value="reject">
                        <x-ui.textarea name="reason" label="Raison (transmise au fournisseur)" rows="2" required/>
                        <div class="flex gap-3"><button class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white">Confirmer le refus</button><button type="button" @click="reject = false" class="btn-secondary">Retour</button></div>
                    </form>
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
                <h2 class="text-xl font-black">Lignes de commande</h2>
                <div class="mt-4">@include('modules.suppliers._order-lines')</div>
                @if($order->notes)<p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600"><span class="font-semibold text-slate-800">Message au fournisseur :</span> {{ $order->notes }}</p>@endif
            </section>

            {{-- Livraisons --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div><h2 class="text-xl font-black">Livraisons</h2><p class="mt-1 text-sm text-slate-500">Chaque expédition du fournisseur crée un lot tracé avec son certificat. Confirmez la réception à l’arrivée de la marchandise.</p></div>
                </div>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($order->lines->flatMap->batches->sortByDesc('created_at') as $batch)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div>
                                <p class="font-bold text-slate-900">{{ $batch->batch_code }} <span class="font-normal text-slate-500">· {{ $fmt((float) $batch->quantity_kg) }} kg de {{ $batch->material?->name }} · {{ $batch->origin_country }}</span></p>
                                <p class="mt-0.5 text-xs text-slate-500">Expédié le {{ $batch->created_at->format('d/m/Y') }}
                                    @foreach($batch->certificates as $certificate) · {{ $certificate->displayName() }} ({{ strtolower($certificate->statusLabel()) }})@endforeach
                                </p>
                            </div>
                            @if($batch->received_at)
                                <x-module.badge tone="good">Reçu le {{ $batch->received_at->format('d/m/Y') }}</x-module.badge>
                            @else
                                <form method="POST" action="{{ route('brand.purchase-orders.receive', $batch) }}">@csrf<x-module.button module="suppliers" variant="soft">Confirmer la réception</x-module.button></form>
                            @endif
                        </div>
                    @empty
                        <p class="py-4 text-sm text-slate-500">{{ $order->acceptsShipment() ? 'Aucune livraison pour l’instant : le fournisseur expédiera depuis son espace.' : 'Aucune livraison.' }}</p>
                    @endforelse
                </div>
            </section>

            @if($order->canCloseShort() || $order->isCancellable())
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]" x-data="{ open: {{ $errors->has('close_reason') ? 'true' : 'false' }} }">
                    <h2 class="text-lg font-black">{{ $order->canCloseShort() ? 'Solder la commande' : 'Annuler la commande' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $order->canCloseShort() ? 'Vous n’attendez plus le reste ? La commande est clôturée avec ce qui a été livré ; le reliquat est abandonné et tracé.' : 'Rien n’a encore été expédié : vous pouvez annuler sans frais.' }}</p>
                    <button type="button" x-show="!open" @click="open = true" class="mt-4 rounded-lg px-4 py-2.5 text-sm font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 hover:bg-rose-50">{{ $order->canCloseShort() ? 'Solder avec reliquat' : 'Annuler la commande' }}</button>
                    <form method="POST" x-cloak x-show="open" action="{{ $order->canCloseShort() ? route('brand.purchase-orders.close', $order) : route('brand.purchase-orders.update', $order) }}" class="mt-4 space-y-3">
                        @csrf
                        @unless($order->canCloseShort()) @method('PATCH')<input type="hidden" name="status" value="cancelled"> @endunless
                        <x-ui.textarea name="close_reason" label="Raison" rows="2" :required="$order->canCloseShort()"/>
                        <div class="flex gap-3"><button class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white">Confirmer</button><button type="button" @click="open = false" class="btn-secondary">Retour</button></div>
                    </form>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
                <h2 class="text-lg font-black">Suivi</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Fournisseur</dt><dd class="text-right font-semibold"><a class="text-purple-700 hover:underline" href="{{ route('brand.suppliers.show', $order->supplier) }}">{{ $order->supplier->name }}</a></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Accès fournisseur</dt><dd class="text-right font-semibold">{{ $order->supplier->user ? 'Actif' : 'Non créé' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Livraison attendue</dt><dd class="text-right font-semibold">{{ $order->expected_at?->format('d/m/Y') ?? '—' }}</dd></div>
                    @if($order->accepted_at)<div class="flex justify-between gap-3"><dt class="text-slate-500">Acceptée le</dt><dd class="text-right font-semibold">{{ $order->accepted_at->format('d/m/Y') }}</dd></div>@endif
                    @if($order->received_at)<div class="flex justify-between gap-3"><dt class="text-slate-500">Reçue le</dt><dd class="text-right font-semibold">{{ $order->received_at->format('d/m/Y') }}</dd></div>@endif
                    @if($order->close_reason)<div><dt class="text-slate-500">Motif de clôture</dt><dd class="mt-1 font-semibold">{{ $order->close_reason }}</dd></div>@endif
                    @if($order->decline_reason)<div><dt class="text-slate-500">Motif du refus</dt><dd class="mt-1 font-semibold text-rose-700">{{ $order->decline_reason }}</dd></div>@endif
                </dl>
                @if($order->awaitsSupplierAnswer() && ! $order->supplier->user)
                    <p class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Créez l’accès fournisseur pour qu’il puisse répondre.</p>
                @endif
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
                <h2 class="text-lg font-black">Journal de la commande</h2>
                <div class="mt-5">@include('modules.suppliers._order-timeline')</div>
            </section>
        </aside>
    </div>
</x-brand-layout>
