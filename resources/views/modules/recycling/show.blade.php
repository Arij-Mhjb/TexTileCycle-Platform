@php
$layout = ['brand' => 'brand-layout', 'supplier' => 'module.supplier-layout', 'admin' => 'app-layout'][$space];
$prefix = ['brand' => 'brand.recycling', 'supplier' => 'supplier.recycling', 'admin' => 'admin.recycling'][$space];
$kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg';
$money = fn ($value) => number_format(abs((float) $value), 2, ',', ' ').' TND';
$card = 'rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900';
$partnerName = $space === 'supplier' ? $cycle->brand->name : $cycle->supplier->name;
$myTurn = $cycle->nextActor() === $space;
$waitingFor = $cycle->nextActor() === 'brand' ? $cycle->brand->name : $cycle->supplier->name;
$pricing = $cycle->pricing;
$stage = ['estimate' => 'Estimation', 'proposal' => 'Prix proposé', 'final' => 'Prix définitif'][$pricing['stage'] ?? ''] ?? null;
$pieces = (int) $cycle->items->where('kind', 'defective')->sum('pieces');
// Qui paie quoi, dit du point de vue de celui qui regarde.
$bottomLine = $cycle->isBuyback()
    ? ['brand' => 'Vous recevez', 'supplier' => 'Vous versez à la marque', 'admin' => 'Avoir versé à la marque'][$space]
    : ['brand' => 'Vous payez', 'supplier' => 'La marque vous paie', 'admin' => 'La marque paie'][$space];
$bottomAmount = $cycle->isBuyback() ? ($pricing['credit'] ?? 0) : ($pricing['fees'] ?? 0);
@endphp
<x-dynamic-component :component="$layout" :title="$cycle->reference">
    @include('modules.recycling._header', [
        'eyebrow' => 'Demande '.$cycle->reference,
        'title' => $space === 'supplier' ? $cycle->brand->name.' vous confie '.$kg($cycle->totalKg()) : $kg($cycle->totalKg()).' confiés à '.$cycle->supplier->name,
        'subtitle' => 'Envoyée le '.$cycle->created_at->format('d/m/Y').($cycle->requester ? ' par '.$cycle->requester->name : '').' · '.$cycle->modeLabel(),
        'actionsHtml' => '<a href="'.route($prefix.'.index').'" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-teal-100 ring-1 ring-white/20 hover:bg-white/10">← Toutes mes demandes</a>'.($cycle->hasInvoice() ? '<a href="'.route($prefix.'.invoice', $cycle).'" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-teal-400 px-4 py-2.5 text-sm font-black text-slate-950 hover:bg-teal-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M12 12v6m-3-3 3 3 3-3"/></svg>'.$cycle->invoiceLabel().' PDF · '.$cycle->invoice_number.'</a>' : ''),
    ])

    @include('modules.recycling._loop', ['cycle' => $cycle, 'estimate' => $estimate])

    {{-- Où en est-on ? Une phrase, et un seul bouton quand c'est à vous. --}}
    <section class="mt-6 overflow-hidden rounded-3xl border-2 {{ $myTurn ? 'border-teal-600' : 'border-slate-200 dark:border-slate-800' }} bg-white dark:bg-slate-900">
        <div class="grid gap-6 p-6 sm:p-8 {{ $myTurn ? 'lg:grid-cols-[minmax(0,1fr)_420px]' : '' }}">
            <div class="flex gap-4">
                @if($myTurn)
                    <span class="relative grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-teal-700 text-white">
                        <span class="absolute -right-1 -top-1 flex h-4 w-4"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-70"></span><span class="relative inline-flex h-4 w-4 rounded-full bg-teal-500 ring-2 ring-white dark:ring-slate-900"></span></span>
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11V5a2 2 0 1 1 4 0v5m0-1a2 2 0 1 1 4 0v2m0-1a2 2 0 1 1 4 0v4a7 7 0 0 1-7 7h-1a7 7 0 0 1-6-3.4L4.5 15a2 2 0 0 1 3.2-2.4L9 14"/></svg>
                    </span>
                @elseif($cycle->status === 'closed')
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-teal-600 text-white"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg></span>
                @elseif($cycle->status === 'declined')
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-slate-200 text-slate-500 dark:bg-slate-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></span>
                @else
                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12M6 21h12M7 3v3a5 5 0 0 0 10 0V3M7 21v-3a5 5 0 0 1 10 0v3"/></svg></span>
                @endif
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wider {{ $myTurn ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">
                        {{ $myTurn ? 'À vous de jouer' : ($cycle->isOpen() ? ($space === 'admin' ? 'En cours · au tour de '.$waitingFor : 'En attente de '.$waitingFor) : $cycle->statusLabel()) }}
                    </p>
                    <p class="rcy-display mt-1 text-xl font-extrabold leading-snug text-slate-900 dark:text-white sm:text-2xl">{{ $cycle->situation($space) }}</p>
                    @if(! $myTurn && $cycle->isOpen() && $space !== 'admin')
                        <p class="mt-2 text-sm text-slate-500">Vous recevrez une notification dès que {{ $waitingFor }} aura agi.</p>
                    @endif
                    @if($errors->any())
                        <p class="mt-3 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</p>
                    @endif
                </div>
            </div>

            @if($myTurn)
                <div class="rounded-2xl bg-teal-50/70 p-5 dark:bg-teal-950/30">
                    @if($space === 'supplier' && $cycle->status === 'requested')
                        <form method="POST" action="{{ route('supplier.recycling.propose', $cycle) }}" class="space-y-4"
                              x-data="{ material: @js((string) old('output_material_id', $estimate['material']?->id)), kg: @js((string) old('expected_output_kg', $estimate['expected_kg'])),
                                        names: @js($materials->pluck('name', 'id')), t: @js($tariff), inputKg: {{ $cycle->totalKg() }}, pieces: {{ $pieces }}, mode: @js($cycle->mode),
                                        r(v) { return Math.round(v * 100) / 100 },
                                        fees() { const name = this.names[this.material]; return this.r(this.t.collection_flat + this.t.collection_per_kg * this.inputKg) + this.r(this.pieces * this.t.dismantling_per_piece) + this.r(this.inputKg * (this.t.recycling_per_kg[name] ?? this.t.defaults.recycling)) },
                                        credit() { const name = this.names[this.material]; return Math.max(0, this.r((Number(this.kg) || 0) * (this.t.recycled_price[name] ?? this.t.defaults.recycled_price) * this.t.buyback_share) - this.fees()) },
                                        money(v) { return Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TND' } }">
                            @csrf
                            <label class="block font-bold">Vous allez produire
                                <select name="output_material_id" x-model="material" class="field !py-3 text-base" required>
                                    @foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach
                                </select>
                            </label>
                            <label class="block font-bold">Environ combien de kilos ?
                                <span class="mt-1 flex items-center gap-2">
                                    <input type="number" step="0.1" min="0.1" max="{{ $cycle->totalKg() }}" name="expected_output_kg" x-model="kg" class="field !mt-0 !py-3 text-lg font-black" required>
                                    <span class="font-bold text-slate-500">kg</span>
                                </span>
                                <span class="mt-1 block text-xs font-normal text-slate-500">Notre estimation : {{ $kg($estimate['expected_kg']) }} (rendement {{ $estimate['yield'] }} %). Maximum {{ $kg($cycle->totalKg()) }}.</span>
                            </label>
                            <div class="rounded-xl bg-white p-4 text-sm ring-1 ring-teal-100 dark:bg-slate-900 dark:ring-teal-900">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Prix calculé automatiquement</p>
                                <p class="mt-1" x-show="mode !== 'buyback'">La marque vous paiera <strong class="text-lg text-teal-800 dark:text-teal-200" x-text="money(fees())"></strong> de frais de recyclage.</p>
                                <p class="mt-1" x-show="mode === 'buyback'">Vous gardez la matière et versez à la marque un avoir de <strong class="text-lg text-teal-800 dark:text-teal-200" x-text="money(credit())"></strong>.</p>
                            </div>
                            <details class="text-sm" @if(old('ready_by') || old('supplier_message')) open @endif>
                                <summary class="cursor-pointer font-bold text-slate-600 dark:text-slate-300">Ajouter une date ou un message (facultatif)</summary>
                                <div class="mt-3 space-y-3">
                                    <label class="block font-bold">Prête le<input type="date" name="ready_by" value="{{ old('ready_by') }}" min="{{ now()->toDateString() }}" class="field"></label>
                                    <label class="block font-bold">Message à la marque<textarea name="supplier_message" rows="2" class="field">{{ old('supplier_message') }}</textarea></label>
                                </div>
                            </details>
                            <x-module.button module="loop" class="w-full !py-4 !text-base">{{ $cycle->nextAction() }}</x-module.button>
                        </form>
                        <details class="mt-4 text-sm">
                            <summary class="cursor-pointer font-semibold text-slate-500">Je ne peux pas recycler ces articles</summary>
                            <form method="POST" action="{{ route('supplier.recycling.decline', $cycle) }}" class="mt-3 space-y-2">@csrf
                                <textarea name="decline_reason" rows="2" class="field" placeholder="Dites pourquoi (ex. matière que nous ne savons pas recycler)" required></textarea>
                                <button class="w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 font-bold text-rose-700 hover:bg-rose-50">Refuser la demande</button>
                            </form>
                        </details>
                    @elseif($space === 'brand' && $cycle->status === 'proposed')
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Matière produite</dt><dd class="font-bold">{{ $cycle->outputMaterial?->name }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Quantité prévue</dt><dd class="font-bold">{{ $kg($cycle->expected_output_kg) }}</dd></div>
                            @if($cycle->ready_by)<div class="flex justify-between gap-3"><dt class="text-slate-500">Prête le</dt><dd class="font-bold">{{ $cycle->ready_by->format('d/m/Y') }}</dd></div>@endif
                            <div class="flex justify-between gap-3 border-t border-teal-100 pt-2 dark:border-teal-900"><dt class="font-bold">{{ $bottomLine }}</dt><dd class="text-lg font-black text-teal-800 dark:text-teal-200">{{ $money($bottomAmount) }}</dd></div>
                        </dl>
                        @if($cycle->supplier_message)<p class="mt-3 rounded-xl bg-white p-3 text-sm dark:bg-slate-900">« {{ $cycle->supplier_message }} »</p>@endif
                        <form method="POST" action="{{ route('brand.recycling.accept', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full !py-4 !text-base">{{ $cycle->nextAction() }}</x-module.button></form>
                        <details class="mt-4 text-sm">
                            <summary class="cursor-pointer font-semibold text-slate-500">Refuser</summary>
                            <form method="POST" action="{{ route('brand.recycling.decline', $cycle) }}" class="mt-3 space-y-2">@csrf
                                <textarea name="decline_reason" rows="2" class="field" placeholder="Ex. nous n’avons plus besoin de cette matière" required></textarea>
                                <button class="w-full rounded-xl border border-rose-200 bg-white px-3 py-2.5 font-bold text-rose-700 hover:bg-rose-50">Refuser la proposition</button>
                            </form>
                        </details>
                    @elseif($space === 'brand' && $cycle->status === 'accepted')
                        <ol class="space-y-2 text-sm">
                            <li class="flex gap-2"><span class="font-black text-teal-700">1.</span> Emballez les {{ $kg($cycle->totalKg()) }} ({{ $cycle->items->count() }} lot(s)).</li>
                            <li class="flex gap-2"><span class="font-black text-teal-700">2.</span> Écrivez « {{ $cycle->reference }} » sur le colis.</li>
                            <li class="flex gap-2"><span class="font-black text-teal-700">3.</span> Remettez-le au transporteur, puis cliquez ici.</li>
                        </ol>
                        <form method="POST" action="{{ route('brand.recycling.ship', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full !py-4 !text-base">{{ $cycle->nextAction() }}</x-module.button></form>
                    @elseif($space === 'supplier' && $cycle->status === 'in_transit')
                        <form method="POST" action="{{ route('supplier.recycling.receive', $cycle) }}" class="space-y-3">@csrf
                            <label class="block font-bold">Poids sur la balance
                                <span class="mt-1 flex items-center gap-2"><input type="number" step="0.1" min="0.1" name="received_kg" value="{{ old('received_kg', $cycle->totalKg()) }}" class="field !mt-0 !py-3 text-lg font-black" required><span class="font-bold text-slate-500">kg</span></span>
                            </label>
                            <p class="text-xs text-slate-500">La marque a annoncé {{ $kg($cycle->totalKg()) }}. Un écart de ± 10 % est accepté. Le prix final sera calculé sur ce poids.</p>
                            <x-module.button module="loop" class="w-full !py-4 !text-base">{{ $cycle->nextAction() }}</x-module.button>
                        </form>
                    @elseif($space === 'supplier' && $cycle->status === 'received')
                        <form method="POST" action="{{ route('supplier.recycling.transform', $cycle) }}" enctype="multipart/form-data" class="space-y-3" x-data="{ file: '' }">@csrf
                            <label class="block font-bold">{{ $cycle->outputMaterial?->name }} obtenu
                                <span class="mt-1 flex items-center gap-2"><input type="number" step="0.1" min="0.1" max="{{ $cycle->received_kg }}" name="output_kg" value="{{ old('output_kg', $cycle->expected_output_kg) }}" class="field !mt-0 !py-3 text-lg font-black" required><span class="font-bold text-slate-500">kg</span></span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-teal-300 bg-white p-4 dark:border-teal-800 dark:bg-slate-900">
                                <svg class="h-6 w-6 shrink-0 text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h2l2-2h6l2 2h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                                <span class="min-w-0 flex-1 text-sm"><span class="block font-bold" x-text="file || 'Photo ou bon de production'"></span><span class="block text-slate-500">Obligatoire : c’est la preuve pour la marque.</span></span>
                                <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required class="sr-only" @change="file = $event.target.files[0]?.name || ''">
                            </label>
                            <x-module.button module="loop" class="w-full !py-4 !text-base">{{ $cycle->nextAction() }}</x-module.button>
                        </form>
                    @elseif($space === 'supplier' && $cycle->status === 'transformed')
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            @if($cycle->isBuyback())
                                Vous gardez les {{ $kg($cycle->output_kg) }} de {{ mb_strtolower($cycle->outputMaterial?->name) }} et versez <strong>{{ $money($bottomAmount) }}</strong> à {{ $cycle->brand->name }}.
                            @else
                                Les {{ $kg($cycle->output_kg) }} de {{ mb_strtolower($cycle->outputMaterial?->name) }} deviennent un <strong>nouveau lot traçable</strong> chez {{ $cycle->brand->name }}.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('supplier.recycling.deliver', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full !py-4 !text-base">{{ $cycle->nextAction() }}</x-module.button></form>
                    @elseif($space === 'brand' && $cycle->status === 'delivered')
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            @if($cycle->isBuyback())
                                Vérifiez que vous avez bien reçu l’avoir de <strong>{{ $money($bottomAmount) }}</strong>.
                            @else
                                Le lot <strong>{{ $cycle->outputBatch?->batch_code }}</strong> ({{ $kg($cycle->output_kg) }}) arrive. À la réception, il rejoindra vos lots et pourra être relié à vos produits.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('brand.recycling.close', $cycle) }}" class="mt-4">@csrf<x-module.button module="loop" class="w-full !py-4 !text-base">{{ $cycle->nextAction() }}</x-module.button></form>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-6">
            {{-- Combien ça coûte ? --}}
            @if($pricing)
                <section class="{{ $card }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg font-black">Combien ça coûte ?</h2>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $cycle->priceIsFinal() ? 'bg-teal-700 text-white' : 'bg-teal-50 text-teal-800 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-200 dark:ring-teal-800' }}">{{ $stage }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Calculé automatiquement par la plateforme{{ $cycle->priceIsFinal() ? ', sur le poids réellement pesé.' : '. Il sera recalculé sur le poids réellement pesé.' }}</p>
                    <dl class="mt-5 space-y-3 text-sm">
                        @foreach($pricing['lines'] ?? [] as $line)
                            <div class="flex items-baseline justify-between gap-4">
                                <dt><span class="font-semibold">{{ $line['label'] }}</span> <span class="block text-xs text-slate-400 sm:inline">{{ $line['detail'] }}</span></dt>
                                <dd class="shrink-0 font-semibold tabular-nums">{{ $money($line['amount']) }}</dd>
                            </div>
                        @endforeach
                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-3 dark:border-slate-800"><dt class="font-bold">Frais de recyclage</dt><dd class="font-bold tabular-nums">{{ $money($pricing['fees']) }}</dd></div>
                        @if($cycle->isBuyback())
                            <div class="flex justify-between gap-4"><dt>Rachat de {{ $kg($pricing['output_kg']) }} de {{ mb_strtolower($pricing['material']) }} <span class="text-xs text-slate-400">({{ (int) (\App\Services\RecyclingPricing::BUYBACK_SHARE * 100) }} % du prix du marché)</span></dt><dd class="font-semibold tabular-nums text-teal-700 dark:text-teal-300">+ {{ $money($pricing['material_value']) }}</dd></div>
                        @endif
                    </dl>
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-teal-700 px-5 py-4 text-white">
                        <span class="font-bold">{{ $bottomLine }}</span>
                        <span class="rcy-display text-3xl font-extrabold tabular-nums">{{ $money($bottomAmount) }}</span>
                    </div>
                    @if(! $cycle->isBuyback() && ($pricing['savings'] ?? 0) > 0)
                        <div class="mt-4 flex items-center gap-4 rounded-2xl bg-teal-50 p-4 text-sm dark:bg-teal-950/40">
                            <span class="rcy-display shrink-0 whitespace-nowrap text-2xl font-extrabold text-teal-800 dark:text-teal-200">−{{ (int) round($pricing['savings'] / max(1, $pricing['new_value']) * 100) }} %</span>
                            <span>Acheter {{ $kg($pricing['output_kg']) }} de matière neuve coûterait <strong>{{ $money($pricing['new_value']) }}</strong>. {{ $space === 'brand' ? 'Vous économisez' : 'La marque économise' }} <strong>{{ $money($pricing['savings']) }}</strong>.</span>
                        </div>
                    @endif
                </section>
            @endif

            {{-- Ce qui est envoyé --}}
            <section class="{{ $card }}">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-lg font-black">Ce qui est envoyé</h2>
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
                                </p>
                                @if($item->issue)<p class="mt-2 text-sm text-slate-700 dark:text-slate-200">Problème : {{ $item->issue }}</p>@endif
                                @if($ai)
                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                        <x-module.ai-badge :source="$ai['source'] ?? 'none'"/>
                                        @foreach($ai['findings'] ?? [] as $finding)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $finding['label'] }} · {{ $finding['confidence'] }} %</span>
                                        @endforeach
                                        @if(isset($ai['consistent']) && $ai['consistent'] !== null)
                                            <span class="font-semibold {{ $ai['consistent'] ? 'text-teal-700' : 'text-amber-700' }}">{{ $ai['consistent'] ? '✓ la photo confirme le défaut' : '⚠ défaut non visible sur la photo' }}</span>
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

            {{-- Résultat --}}
            @if($cycle->output_kg)
                <section class="{{ $card }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <h2 class="text-lg font-black">Résultat du recyclage</h2>
                        @if($cycle->proof_path)<x-module.button module="loop" variant="soft" :href="route($prefix.'.document', [$cycle, 'proof'])" target="_blank">Voir la preuve</x-module.button>@endif
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-6">
                        @php $rate = $cycle->yieldRate() ?? 0; @endphp
                        <div class="relative h-28 w-28 shrink-0 rounded-full" style="background: conic-gradient(#0d9488 0% {{ $rate }}%, #e2e8f0 {{ $rate }}% 100%)">
                            <div class="absolute inset-[10px] grid place-items-center rounded-full bg-white text-center dark:bg-slate-900">
                                <span><span class="rcy-display block text-2xl font-extrabold text-teal-800 dark:text-teal-200">{{ $rate }} %</span><span class="text-[11px] text-slate-500">rendement</span></span>
                            </div>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p><strong>{{ $kg($cycle->received_kg) }}</strong> reçus → <strong>{{ $kg($cycle->output_kg) }}</strong> de {{ mb_strtolower($cycle->outputMaterial?->name) }}</p>
                            @if($cycle->outputBatch)<p>Lot créé : <strong>{{ $cycle->outputBatch->batch_code }}</strong> ({{ $cycle->outputBatch->received_at ? 'reçu par la marque' : 'en livraison' }})</p>@endif
                            <p class="text-slate-500">{{ $kg((float) $cycle->received_kg - (float) $cycle->output_kg) }} de pertes (poussières, fibres trop courtes).</p>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="{{ $card }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $space === 'supplier' ? 'Marque' : 'Fournisseur' }}</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-teal-700 text-lg font-black text-white">{{ mb_strtoupper(mb_substr($partnerName, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-lg font-black">{{ $partnerName }}</p>
                        @if($space !== 'supplier')<p class="text-sm text-slate-500">{{ $cycle->supplier->city ? $cycle->supplier->city.', ' : '' }}{{ $cycle->supplier->country }}</p>@endif
                    </div>
                </div>
                @if($space === 'admin')<p class="mt-3 text-sm text-slate-500">Marque : <strong class="text-slate-700 dark:text-slate-200">{{ $cycle->brand->name }}</strong></p>@endif
            </section>

            <section class="{{ $card }}">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Historique</p>
                <ol class="mt-4 space-y-4 border-l-2 border-teal-100 pl-5 text-sm dark:border-teal-900">
                    @foreach([
                        ['Demande envoyée', $cycle->created_at], ['Proposition du fournisseur', $cycle->proposed_at], ['Proposition acceptée', $cycle->accepted_at],
                        ['Colis envoyé', $cycle->shipped_at], ['Colis reçu et pesé', $cycle->received_at], ['Matière recyclée produite', $cycle->transformed_at],
                        [$cycle->isBuyback() ? 'Avoir versé' : 'Matière livrée', $cycle->delivered_at], ['Terminé', $cycle->closed_at], ['Refusée', $cycle->declined_at],
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
