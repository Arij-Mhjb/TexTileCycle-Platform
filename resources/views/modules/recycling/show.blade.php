@php
$layout = ['brand' => 'brand-layout', 'supplier' => 'module.supplier-layout', 'admin' => 'app-layout'][$space];
$prefix = ['brand' => 'brand.recycling', 'supplier' => 'supplier.recycling', 'admin' => 'admin.recycling'][$space];
$kg = fn ($value) => number_format((float) $value, 2, ',', ' ').' kg';
$money = fn ($value) => number_format((float) $value, 2, ',', ' ').' TND';
$card = 'rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900';
$partner = $space === 'supplier' ? $cycle->brand->name : $cycle->supplier->name;
$myTurn = $cycle->nextActor() === $space;
$short = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ');
@endphp
<x-dynamic-component :component="$layout" :title="$cycle->reference">
    @include('modules.recycling._header', [
        'eyebrow' => 'Boucle '.$cycle->reference,
        'title' => $space === 'supplier' ? $cycle->brand->name.' vous confie '.$short($cycle->totalKg()).' kg' : $short($cycle->totalKg()).' kg confiés à '.$cycle->supplier->name,
        'subtitle' => 'Demande du '.$cycle->created_at->format('d/m/Y').($cycle->requester ? ' par '.$cycle->requester->name : '').' · '.$cycle->statusLabel(),
        'actionsHtml' => '<a href="'.route($prefix.'.index').'" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-teal-800 ring-1 ring-teal-200 hover:bg-teal-50 dark:text-teal-200 dark:ring-teal-800">← Toutes les boucles</a>',
    ])

    @include('modules.recycling._loop', ['cycle' => $cycle, 'estimate' => $estimate])

    @if($errors->any())
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            {{-- Articles confiés --}}
            <section class="{{ $card }}">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-lg font-black">Articles confiés</h2>
                    <p class="text-sm text-slate-500">{{ $cycle->items->count() }} lot(s) · {{ $kg($cycle->totalKg()) }}@if($cycle->totalPieces()) · {{ $cycle->totalPieces() }} pièces @endif</p>
                </div>
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($cycle->items as $item)
                        @php $ai = $item->ai_analysis; @endphp
                        <div class="flex flex-wrap gap-4 py-4 first:pt-0 last:pb-0">
                            @if($item->photo_path)
                                <a href="{{ route($prefix.'.document', [$cycle, 'photo', $item->id]) }}" target="_blank" class="block h-24 w-24 shrink-0 overflow-hidden rounded-2xl bg-slate-100">
                                    <img src="{{ route($prefix.'.document', [$cycle, 'photo', $item->id]) }}" alt="Photo : {{ $item->label }}" class="h-full w-full object-cover">
                                </a>
                            @else
                                <span class="grid h-24 w-24 shrink-0 place-items-center rounded-2xl bg-teal-50 text-teal-600 dark:bg-teal-950">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 4 4 7l2 3 2-1v11h8V9l2 1 2-3-4-3h-2a2 2 0 0 1-4 0z"/></svg>
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-module.badge tone="loop">{{ $item->kindLabel() }}</x-module.badge>
                                    <p class="font-black">{{ $item->label }}</p>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $kg($item->quantity_kg) }}@if($item->pieces) · {{ $item->pieces }} pièces @endif
                                    @if($item->mainMaterial()) · {{ $item->mainMaterial()->name }}@endif
                                    @if($item->product) · produit {{ $item->product->name }}@endif
                                </p>
                                @if($item->issue)<p class="mt-2 text-sm text-slate-700 dark:text-slate-200">« {{ $item->issue }} »</p>@endif
                                @if($ai)
                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                        <x-module.ai-badge :source="$ai['source'] ?? 'none'"/>
                                        @foreach($ai['findings'] ?? [] as $finding)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $finding['label'] }} · {{ $finding['confidence'] }} %</span>
                                        @endforeach
                                        @if(isset($ai['consistent']) && $ai['consistent'] !== null)
                                            <span class="font-semibold {{ $ai['consistent'] ? 'text-teal-700' : 'text-amber-700' }}">{{ $ai['consistent'] ? '✓ cohérent avec la déclaration' : '⚠ défaut non visible sur la photo' }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($cycle->brand_message || $cycle->wished_pickup_at)
                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-800/60">
                        @if($cycle->brand_message)<p>« {{ $cycle->brand_message }} » <span class="text-slate-400">— {{ $cycle->brand->name }}</span></p>@endif
                        @if($cycle->wished_pickup_at)<p class="mt-1 text-slate-500">Enlèvement souhaité le {{ $cycle->wished_pickup_at->format('d/m/Y') }}</p>@endif
                    </div>
                @endif
            </section>

            {{-- Proposition du fournisseur --}}
            @if($cycle->mode)
                <section class="{{ $card }}">
                    <h2 class="text-lg font-black">Proposition de {{ $cycle->supplier->name }}</h2>
                    <dl class="mt-4 grid gap-3 sm:grid-cols-4">
                        @foreach([
                            ['Matière produite', $cycle->outputMaterial?->name],
                            ['Quantité prévue', $kg($cycle->expected_output_kg)],
                            [$cycle->mode === 'sale' ? 'Prix de vente' : 'Frais de recyclage', (float) $cycle->unit_price > 0 ? $money($cycle->unit_price).' / kg' : 'Gratuit'],
                            ['Prête le', $cycle->ready_by?->format('d/m/Y') ?? '—'],
                        ] as [$label, $value])
                            <div class="rounded-2xl bg-teal-50/60 p-4 dark:bg-teal-950/30"><dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt><dd class="mt-1 font-black">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-sm"><strong>{{ $cycle->modeLabel() }}</strong>@if($cycle->amount()) · total estimé <strong>{{ $money($cycle->amount()) }}</strong>@endif</p>
                    @if($cycle->supplier_message)<p class="mt-3 rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-800/60">« {{ $cycle->supplier_message }} » <span class="text-slate-400">— {{ $cycle->supplier->name }}</span></p>@endif
                </section>
            @endif

            {{-- Résultat --}}
            @if($cycle->output_kg)
                <section class="{{ $card }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black">Résultat du recyclage</h2>
                            <p class="text-sm text-slate-500">{{ $kg($cycle->received_kg) }} reçus → {{ $kg($cycle->output_kg) }} de {{ $cycle->outputMaterial?->name }}</p>
                        </div>
                        @if($cycle->proof_path)<x-module.button module="loop" variant="soft" :href="route($prefix.'.document', [$cycle, 'proof'])" target="_blank">Voir la preuve</x-module.button>@endif
                    </div>
                    <div class="mt-5 flex items-center gap-6">
                        @php $rate = $cycle->yieldRate() ?? 0; @endphp
                        <div class="relative h-28 w-28 shrink-0 rounded-full" style="background: conic-gradient(#0d9488 0% {{ $rate }}%, #e2e8f0 {{ $rate }}% 100%)">
                            <div class="absolute inset-[10px] grid place-items-center rounded-full bg-white text-center dark:bg-slate-900">
                                <span><span class="rcy-display block text-2xl font-extrabold text-teal-800 dark:text-teal-200">{{ $rate }} %</span><span class="text-[11px] text-slate-500">rendement</span></span>
                            </div>
                        </div>
                        <div class="space-y-1 text-sm">
                            @if($cycle->outputBatch)<p>Lot créé : <strong>{{ $cycle->outputBatch->batch_code }}</strong> ({{ $cycle->outputBatch->received_at ? 'reçu par la marque' : 'en livraison' }})</p>@endif
                            @if($cycle->amount())<p>{{ $cycle->mode === 'sale' ? 'Montant de la vente' : 'Frais de recyclage' }} : <strong>{{ $money($cycle->amount()) }}</strong></p>@endif
                            <p class="text-slate-500">{{ $kg((float) $cycle->received_kg - (float) $cycle->output_kg) }} de pertes (poussières, fibres trop courtes).</p>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            {{-- Ce que je dois faire maintenant --}}
            @if($myTurn)
                <section class="overflow-hidden rounded-3xl border-2 border-teal-600 bg-white dark:bg-slate-900">
                    <p class="bg-teal-700 px-6 py-3 text-sm font-bold text-white">À vous : {{ $cycle->nextAction() }}</p>
                    <div class="p-6">
                        @if($space === 'supplier' && $cycle->status === 'requested')
                            <form method="POST" action="{{ route('supplier.recycling.propose', $cycle) }}" class="space-y-4" x-data="{ mode: @js(old('mode', 'restitution')) }">
                                @csrf
                                <div class="rounded-2xl bg-teal-50 p-3 text-sm text-teal-900 dark:bg-teal-950/50 dark:text-teal-100">
                                    Estimation : ≈ <strong>{{ number_format($estimate['expected_kg'], 1, ',', ' ') }} kg</strong> de <strong>{{ $estimate['material_name'] }}</strong> (rendement {{ $estimate['yield'] }} %).
                                </div>
                                <label class="block text-sm font-bold">Matière que vous produirez
                                    <select name="output_material_id" class="field" required>
                                        @foreach($materials as $material)<option value="{{ $material->id }}" @selected((int) old('output_material_id', $estimate['material']?->id) === $material->id)>{{ $material->name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="block text-sm font-bold">Quantité prévue (kg)
                                    <input type="number" step="0.1" min="0.1" max="{{ $cycle->totalKg() }}" name="expected_output_kg" value="{{ old('expected_output_kg', $estimate['expected_kg']) }}" class="field" required>
                                </label>
                                <fieldset>
                                    <legend class="text-sm font-bold">Ensuite, la matière recyclée…</legend>
                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        @foreach(['restitution' => ['est rendue', 'à la marque'], 'sale' => ['est revendue', 'à la marque']] as $value => [$a, $b])
                                            <label class="cursor-pointer rounded-xl border p-3 text-center text-sm" :class="mode === '{{ $value }}' ? 'border-teal-600 bg-teal-50 font-bold dark:bg-teal-950/40' : 'border-slate-200 dark:border-slate-700'">
                                                <input type="radio" name="mode" value="{{ $value }}" x-model="mode" class="sr-only">{{ $a }}<span class="block text-xs font-normal text-slate-500">{{ $b }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <label class="block text-sm font-bold"><span x-text="mode === 'sale' ? 'Prix de vente (TND / kg)' : 'Frais de recyclage (TND / kg, 0 = gratuit)'"></span>
                                    <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', 0) }}" class="field">
                                </label>
                                <label class="block text-sm font-bold">Prête le
                                    <input type="date" name="ready_by" value="{{ old('ready_by') }}" min="{{ now()->toDateString() }}" class="field">
                                </label>
                                <label class="block text-sm font-bold">Message à la marque
                                    <textarea name="supplier_message" rows="2" class="field">{{ old('supplier_message') }}</textarea>
                                </label>
                                <x-module.button module="loop" class="w-full">Envoyer ma proposition</x-module.button>
                            </form>
                            <details class="mt-4 text-sm">
                                <summary class="cursor-pointer font-semibold text-slate-500">Je ne peux pas reprendre ces articles</summary>
                                <form method="POST" action="{{ route('supplier.recycling.decline', $cycle) }}" class="mt-3 space-y-2">@csrf
                                    <textarea name="decline_reason" rows="2" class="field" placeholder="Expliquez pourquoi (matière non recyclable chez nous…)" required></textarea>
                                    <button class="w-full rounded-xl border border-rose-200 px-3 py-2 font-bold text-rose-700 hover:bg-rose-50">Refuser la demande</button>
                                </form>
                            </details>
                        @elseif($space === 'brand' && $cycle->status === 'proposed')
                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $cycle->supplier->name }} propose de produire <strong>{{ $kg($cycle->expected_output_kg) }}</strong> de <strong>{{ $cycle->outputMaterial?->name }}</strong>, {{ mb_strtolower($cycle->modeLabel()) }}{{ $cycle->amount() ? ' pour '.$money($cycle->amount()) : ' sans frais' }}.</p>
                            <form method="POST" action="{{ route('brand.recycling.accept', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full">Accepter la proposition</x-module.button></form>
                            <details class="mt-4 text-sm">
                                <summary class="cursor-pointer font-semibold text-slate-500">Refuser</summary>
                                <form method="POST" action="{{ route('brand.recycling.decline', $cycle) }}" class="mt-3 space-y-2">@csrf
                                    <textarea name="decline_reason" rows="2" class="field" placeholder="Ex. prix trop élevé" required></textarea>
                                    <button class="w-full rounded-xl border border-rose-200 px-3 py-2 font-bold text-rose-700 hover:bg-rose-50">Refuser la proposition</button>
                                </form>
                            </details>
                        @elseif($space === 'brand' && $cycle->status === 'accepted')
                            <p class="text-sm text-slate-600 dark:text-slate-300">Emballez les {{ $kg($cycle->totalKg()) }} et remettez-les au transporteur.</p>
                            <form method="POST" action="{{ route('brand.recycling.ship', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full">Les articles sont partis</x-module.button></form>
                        @elseif($space === 'supplier' && $cycle->status === 'in_transit')
                            <form method="POST" action="{{ route('supplier.recycling.receive', $cycle) }}" class="space-y-3">@csrf
                                <label class="block text-sm font-bold">Poids pesé à l’arrivée (kg)
                                    <input type="number" step="0.1" min="0.1" name="received_kg" value="{{ old('received_kg', $cycle->totalKg()) }}" class="field" required>
                                </label>
                                <p class="text-xs text-slate-500">Annoncé : {{ $kg($cycle->totalKg()) }} (écart toléré ± 10 %).</p>
                                <x-module.button module="loop" class="w-full">Confirmer la réception</x-module.button>
                            </form>
                        @elseif($space === 'supplier' && $cycle->status === 'received')
                            <form method="POST" action="{{ route('supplier.recycling.transform', $cycle) }}" enctype="multipart/form-data" class="space-y-3">@csrf
                                <label class="block text-sm font-bold">{{ $cycle->outputMaterial?->name }} produit (kg)
                                    <input type="number" step="0.1" min="0.1" max="{{ $cycle->received_kg }}" name="output_kg" value="{{ old('output_kg', $cycle->expected_output_kg) }}" class="field" required>
                                </label>
                                <label class="block text-sm font-bold">Preuve <span class="font-normal text-slate-500">(photo de la matière, bon de production)</span>
                                    <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required class="mt-2 block w-full text-sm">
                                </label>
                                <x-module.button module="loop" class="w-full">Déclarer la production</x-module.button>
                            </form>
                        @elseif($space === 'supplier' && $cycle->status === 'transformed')
                            <p class="text-sm text-slate-600 dark:text-slate-300">La livraison crée un <strong>nouveau lot traçable</strong> de {{ $kg($cycle->output_kg) }} de {{ $cycle->outputMaterial?->name }} chez {{ $cycle->brand->name }}.</p>
                            <form method="POST" action="{{ route('supplier.recycling.deliver', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full">Livrer la matière recyclée</x-module.button></form>
                        @elseif($space === 'brand' && $cycle->status === 'delivered')
                            <p class="text-sm text-slate-600 dark:text-slate-300">Le lot <strong>{{ $cycle->outputBatch?->batch_code }}</strong> ({{ $kg($cycle->output_kg) }} de {{ $cycle->outputMaterial?->name }}) est en route. Confirmez à l’arrivée : il rejoindra vos lots et pourra être relié à vos produits.</p>
                            <form method="POST" action="{{ route('brand.recycling.close', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full">J’ai reçu la matière recyclée</x-module.button></form>
                        @endif
                    </div>
                </section>
            @elseif($cycle->isOpen())
                <section class="rounded-3xl border border-slate-200 bg-slate-50 p-6 text-sm dark:border-slate-800 dark:bg-slate-800/40">
                    <p class="font-bold">En attente de {{ $cycle->nextActor() === 'brand' ? $cycle->brand->name : $cycle->supplier->name }}</p>
                    <p class="mt-1 text-slate-500">Prochaine étape : {{ mb_strtolower($cycle->nextAction()) }}. Vous serez notifié.</p>
                </section>
            @endif

            {{-- Partenaire --}}
            <section class="{{ $card }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $space === 'supplier' ? 'Marque' : 'Fournisseur' }}</p>
                <p class="mt-2 text-lg font-black">{{ $partner }}</p>
                @if($space !== 'supplier')<p class="text-sm text-slate-500">{{ $cycle->supplier->city ? $cycle->supplier->city.', ' : '' }}{{ $cycle->supplier->country }}@if($cycle->supplier->compliance_score !== null) · conformité {{ $cycle->supplier->compliance_score }} %@endif</p>@endif
            </section>

            {{-- Journal --}}
            <section class="{{ $card }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Journal</p>
                <ol class="mt-4 space-y-4 border-l-2 border-teal-100 pl-5 text-sm dark:border-teal-900">
                    @foreach([
                        ['Demande envoyée', $cycle->created_at], ['Proposition du fournisseur', $cycle->proposed_at], ['Proposition acceptée', $cycle->accepted_at],
                        ['Articles expédiés', $cycle->shipped_at], ['Articles reçus et pesés', $cycle->received_at], ['Matière recyclée produite', $cycle->transformed_at],
                        ['Matière recyclée livrée', $cycle->delivered_at], ['Boucle fermée', $cycle->closed_at], ['Refusée', $cycle->declined_at],
                    ] as [$label, $date])
                        @if($date)
                            <li class="relative"><span class="absolute -left-[27px] top-1 h-3 w-3 rounded-full bg-teal-600 ring-4 ring-white dark:ring-slate-900"></span>
                                <p class="font-semibold">{{ $label }}</p><p class="text-xs text-slate-500">{{ $date->format('d/m/Y à H:i') }}</p></li>
                        @endif
                    @endforeach
                </ol>
            </section>
        </aside>
    </div>
</x-dynamic-component>
