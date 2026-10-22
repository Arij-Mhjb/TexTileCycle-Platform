{{-- Commande dans l'espace du fournisseur : répondre (accepter / contre-proposer / refuser), puis expédier ligne par ligne. --}}
@php
    $fmt = fn (float $kg) => rtrim(rtrim(number_format($kg, 2, ',', ' '), '0'), ',');
    $shippable = $order->lines->filter(fn ($line) => $line->maxShippableKg() > 0 && ! $line->isFullyShipped());
    $answerTab = old('decision', $errors->has('decline_reason') ? 'decline' : 'accept');
@endphp
<x-module.supplier-layout :title="'Commande '.$order->reference">
    <x-module.hero module="suppliers" :eyebrow="'Bon de commande · '.$order->brand->name" :title="$order->reference"
        :description="'Commandée le '.$order->ordered_at->format('d/m/Y').($order->expected_at ? ', livraison souhaitée le '.$order->expected_at->format('d/m/Y') : '').'. '.$order->statusLabel().'.'">
        <x-slot:stats>
            <x-module.stat label="Statut" :value="$order->shortStatusLabel()"/>
            <x-module.stat label="À livrer" :value="$fmt($order->dueKg()).' kg'" :hint="$order->lines->count().' matière(s)'"/>
            <x-module.stat label="Expédié" :value="$order->shippedPercent().' %'" :hint="$fmt($order->shippedKg()).' kg'"/>
            <x-module.stat label="Montant" :value="$order->total_amount ? number_format((float) $order->total_amount, 0, ',', ' ').' '.$order->currency : '—'"/>
        </x-slot:stats>
    </x-module.hero>

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-xl font-black">Ce que {{ $order->brand->name }} commande</h2>
            <p class="text-sm text-slate-500">À joindre à chaque livraison : le certificat de provenance (GRS, GOTS, RWS, OEKO-TEX…)</p>
        </div>
        <div class="mt-4">@include('modules.suppliers._order-lines')</div>
        @if($order->notes)<p class="mt-4 rounded-xl bg-purple-50 px-4 py-3 text-sm text-purple-950"><span class="font-semibold">Message du client :</span> {{ $order->notes }}</p>@endif
    </section>

    {{-- Réponse : accepter, contre-proposer, refuser --}}
    @if($order->awaitsSupplierAnswer())
        <section class="mb-6 rounded-2xl border-2 border-purple-300 bg-white p-6" x-data="{ tab: '{{ $answerTab }}' }">
            <h2 class="text-xl font-black">Votre réponse</h2>
            <div class="mt-4 inline-flex rounded-lg bg-slate-100 p-1 text-sm font-semibold" role="tablist">
                @foreach(['accept' => 'Accepter', 'counter' => 'Proposer des modifications', 'decline' => 'Refuser'] as $key => $label)
                    <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'" class="rounded-md px-4 py-2 transition-colors">{{ $label }}</button>
                @endforeach
            </div>
            @error('decision')<p class="mt-3 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror

            <form method="POST" action="{{ $answerUrl }}" x-show="tab === 'accept'" class="mt-5 space-y-4">
                @csrf<input type="hidden" name="decision" value="accept">
                <p class="text-sm text-slate-600">Vous livrez toutes les quantités demandées{{ $order->expected_at ? ' pour le '.$order->expected_at->format('d/m/Y') : '' }}.</p>
                <x-ui.textarea name="supplier_note" label="Message (facultatif)" rows="2"/>
                <x-module.button module="suppliers">Accepter la commande</x-module.button>
            </form>

            <form method="POST" action="{{ $answerUrl }}" x-cloak x-show="tab === 'counter'" class="mt-5 space-y-4">
                @csrf<input type="hidden" name="decision" value="counter">
                <p class="text-sm text-slate-600">Stock insuffisant, minimum de production ou délai différent ? Indiquez ce que vous pouvez livrer : {{ $order->brand->name }} acceptera ou refusera.</p>
                <div class="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                    @foreach($order->lines as $line)
                        <label class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                            <span class="text-sm"><span class="font-semibold text-slate-900">{{ $line->material?->name }}</span> <span class="text-slate-500">· demandé {{ $fmt((float) $line->quantity_kg) }} kg</span></span>
                            <span class="flex items-center gap-2"><input type="number" step="0.1" min="0" name="confirmed[{{ $line->id }}]" value="{{ old('confirmed.'.$line->id, $line->quantity_kg) }}" class="field mt-0 w-32 text-right" aria-label="Quantité que vous pouvez livrer"><span class="text-sm text-slate-500">kg</span></span>
                        </label>
                    @endforeach
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-module.field name="proposed_expected_at" type="date" label="Date de livraison possible" :value="$order->expected_at?->toDateString()"/>
                    <x-module.field name="supplier_note" label="Explication pour le client" placeholder="Ex. stock de polyester limité jusqu’en mars"/>
                </div>
                <x-module.button module="suppliers">Envoyer la contre-proposition</x-module.button>
            </form>

            <form method="POST" action="{{ $answerUrl }}" x-cloak x-show="tab === 'decline'" class="mt-5 space-y-4">
                @csrf<input type="hidden" name="decision" value="decline">
                <x-ui.textarea name="decline_reason" label="Raison du refus" rows="3" required/>
                <button class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">Refuser la commande</button>
            </form>
        </section>
    @elseif($order->awaitsBrandDecision())
        <div class="mb-6 rounded-2xl border border-purple-200 bg-purple-50 px-5 py-4 text-sm text-purple-950"><strong>Contre-proposition envoyée.</strong> {{ $order->brand->name }} doit l’accepter avant que vous puissiez expédier.</div>
    @elseif($order->status === 'declined')
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><strong>Commande refusée</strong> le {{ $order->declined_at?->format('d/m/Y') }} : « {{ $order->decline_reason }} »</div>
    @endif

    {{-- Expédier une livraison (partielle ou complète) --}}
    @if($order->acceptsShipment() && $shippable->isNotEmpty())
        @php $firstLine = $shippable->first(); @endphp
        <form method="POST" action="{{ $shipUrl }}" enctype="multipart/form-data" class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)] sm:p-8"
              x-data="{ name: '', lines: {{ json_encode($shippable->mapWithKeys(fn ($line) => [$line->id => ['remaining' => $line->remainingKg(), 'max' => $line->maxShippableKg()]])) }}, line: '{{ old('purchase_order_line_id', $firstLine->id) }}', qty: '{{ old('quantity_kg', $firstLine->remainingKg()) }}' }">
            @csrf
            <h2 class="text-xl font-black">{{ $order->lines->flatMap->batches->isEmpty() ? 'Expédier' : 'Expédier une autre livraison' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Livrez tout d’un coup ou en plusieurs fois : chaque livraison devient un lot tracé avec son certificat. Tolérance de livraison : +{{ (int) (\App\Models\PurchaseOrder::OVER_DELIVERY_TOLERANCE * 100) }} %.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="block"><span class="text-sm font-bold text-slate-700">Ligne de commande <span class="text-rose-500">*</span></span>
                    <select name="purchase_order_line_id" class="field" x-model="line" @change="qty = lines[line].remaining" required>
                        @foreach($shippable as $line)<option value="{{ $line->id }}">{{ $line->material?->name }} — reste {{ $fmt($line->remainingKg()) }} kg</option>@endforeach
                    </select>
                    @error('purchase_order_line_id')<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                </label>
                <label class="block"><span class="text-sm font-bold text-slate-700">Quantité expédiée (kg) <span class="text-rose-500">*</span></span>
                    <input type="number" name="quantity_kg" step="0.1" min="0.1" x-model="qty" required class="field">
                    <span class="mt-1 block text-xs text-slate-500" x-text="'Maximum ' + (lines[line]?.max ?? 0).toLocaleString('fr-FR') + ' kg sur cette ligne'"></span>
                    @error('quantity_kg')<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                </label>
                <x-module.field name="origin_country" label="Pays d’origine de la matière" :value="$order->supplier->country" required/>
                <x-module.field name="production_date" type="date" label="Date de production"/>
                <label class="md:col-span-2 flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed border-pink-300 bg-pink-50/40 p-6 text-center">
                    <span class="text-sm font-bold text-pink-900">Photo du certificat de provenance *</span>
                    <span class="text-xs text-slate-500">GRS, GOTS, RWS, OEKO-TEX… L’IA lit le type, le numéro, l’organisme et les dates.</span>
                    <span class="rounded-lg bg-white px-4 py-2 text-xs font-semibold text-pink-800 ring-1 ring-pink-200" x-text="name || 'Choisir la photo'"></span>
                    <input type="file" name="certificate" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/heic,image/heif" required class="sr-only" @change="name = $event.target.files[0]?.name || ''">
                    @error('certificate')<span class="text-sm font-semibold text-rose-600">{{ $message }}</span>@enderror
                </label>
                <div class="md:col-span-2"><x-ui.textarea name="supplier_note" label="Message pour {{ $order->brand->name }} (facultatif)" rows="2"/></div>
            </div>
            <div class="mt-6 flex justify-end"><x-module.button module="suppliers">Confirmer l’expédition</x-module.button></div>
        </form>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
            <h2 class="text-lg font-black">Vos livraisons</h2>
            <div class="mt-3 divide-y divide-slate-100">
                @forelse($order->lines->flatMap->batches->sortByDesc('created_at') as $batch)
                    <div class="py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-bold">{{ $batch->batch_code }} <span class="font-normal text-slate-500">· {{ $fmt((float) $batch->quantity_kg) }} kg de {{ $batch->material?->name }}</span></p>
                            <x-module.badge :tone="$batch->received_at ? 'good' : 'warn'">{{ $batch->received_at ? 'Reçue par le client' : 'En route' }}</x-module.badge>
                        </div>
                        <div class="mt-3 grid gap-3">
                            @foreach($batch->certificates as $certificate)
                                @include('modules.suppliers._certificate', ['documentRoute' => 'supplier.certificates.document'])
                            @endforeach
                            @unless($batch->certificates->contains(fn ($certificate) => ! $certificate->isNotACertificate() && $certificate->status !== 'rejected'))
                                @include('modules.suppliers._add-certificate', ['action' => route('supplier.batches.certificate', $batch)])
                            @endunless
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-sm text-slate-500">Aucune livraison pour l’instant.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
            <h2 class="text-lg font-black">Historique</h2>
            <div class="mt-5">@include('modules.suppliers._order-timeline')</div>
        </section>
    </div>
</x-module.supplier-layout>
