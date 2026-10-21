@php
$blank = ['kind' => 'defective', 'product_id' => '', 'material_id' => '', 'pieces' => '', 'quantity_kg' => '', 'issue' => ''];
$initialItems = collect(old('items', [$blank]))->values()->map(function ($item, $i) use ($blank) {
    $item = array_merge($blank, $item);
    $item['what'] = $item['product_id'] ? 'p:'.$item['product_id'] : ($item['material_id'] ? 'm:'.$item['material_id'] : '');
    $item['issue'] = (string) $item['issue'];
    $item['photoName'] = '';

    return ['uid' => $i + 1] + $item;
});
$kinds = [
    'defective' => ['Pièces ratées', 'Couture, tache, taille…', '<path d="M8 4 4 7l2 3 2-1v11h8V9l2 1 2-3-4-3h-2a2 2 0 0 1-4 0z"/><path d="m10 13 4 4m0-4-4 4"/>'],
    'surplus' => ['Surplus', 'Rouleaux, fils non utilisés', '<path d="M7 5a3 3 0 1 0 0 6 3 3 0 1 0 0-6Z"/><path d="M7 5h11v12H7M4 8v9a3 3 0 0 0 3 3h11v-3"/>'],
    'offcuts' => ['Chutes', 'Restes de coupe', '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M8.2 8.2 20 20M8.2 15.8 20 4"/>'],
];
$problems = ['Couture ratée', 'Tache', 'Trou ou accroc', 'Mauvaise taille', 'Mauvaise couleur', 'Impression ratée'];
$itemKeys = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'items'));
$startStep = $errors->has('supplier_id') ? 1 : ($itemKeys ? 2 : ($errors->has('mode') ? 3 : ($errors->any() ? 4 : (old('supplier_id', $selectedSupplier) ? 2 : 1))));
$steps = ['Fournisseur', 'Articles', 'Retour', 'Envoi'];
@endphp
<x-brand-layout title="Nouvelle demande de recyclage">
    @include('modules.recycling._header', [
        'eyebrow' => 'Nouvelle demande',
        'title' => 'Recycler mes rebuts',
        'subtitle' => '4 petites étapes. On calcule tout pour vous : la matière que vous obtiendrez et le prix.',
    ])

    @if($suppliers->isEmpty())
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-8 text-amber-900">
            <p class="text-lg font-black">Aucun fournisseur prêt</p>
            <p class="mt-1 text-sm">Aucun de vos fournisseurs n’a encore activé son espace. Ouvrez sa fiche et cliquez sur « Donner l’accès », puis revenez ici.</p>
        </div>
    @else
    <form method="POST" action="{{ route('brand.recycling.store') }}" enctype="multipart/form-data" novalidate
          x-data="recyclingWizard({
              items: @js($initialItems), routes: @js($routes), productMaterial: @js($productMaterial), kindFactors: @js($kindFactors),
              tariff: @js($tariff), supplier: @js((string) old('supplier_id', $selectedSupplier)), mode: @js(old('mode', '')),
              productNames: @js($products->pluck('name', 'id')), materialNames: @js($materials->pluck('name', 'id')),
              supplierNames: @js($suppliers->pluck('name', 'id')), step: {{ $startStep }},
          })"
          @submit="if (sending || step < 4) { $event.preventDefault(); return } sending = true"
          @keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); if (step < 4) next() }">
        @csrf
        <input type="hidden" name="supplier_id" :value="supplier">
        <input type="hidden" name="mode" :value="mode">

        {{-- Barre de progression --}}
        <ol class="mb-6 grid grid-cols-4 gap-2" aria-label="Étapes">
            @foreach($steps as $i => $label)
                <li>
                    <button type="button" @click="go({{ $i + 1 }})" :disabled="{{ $i + 1 }} > reached" class="group w-full text-left disabled:cursor-default">
                        <span class="block h-2 rounded-full transition-colors" :class="step >= {{ $i + 1 }} ? 'bg-teal-600' : 'bg-slate-200 dark:bg-slate-700'"></span>
                        <span class="mt-2 flex items-center gap-2 text-sm font-bold" :class="step === {{ $i + 1 }} ? 'text-teal-800 dark:text-teal-200' : 'text-slate-400'">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs" :class="step > {{ $i + 1 }} ? 'bg-teal-600 text-white' : (step === {{ $i + 1 }} ? 'bg-teal-700 text-white' : 'bg-slate-100 dark:bg-slate-800')">
                                <span x-show="step <= {{ $i + 1 }}">{{ $i + 1 }}</span>
                                <svg x-show="step > {{ $i + 1 }}" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            </span>
                            <span class="hidden sm:inline">{{ $label }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0">
                @if($errors->any())
                    <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        <p class="font-bold">Il manque quelque chose :</p>
                        <ul class="mt-1 list-disc pl-5">@foreach(array_unique($errors->all()) as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <p x-show="hint" x-text="hint" x-cloak class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900"></p>

                {{-- Étape 1 : fournisseur --}}
                <section x-show="step === 1" class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                    <h2 class="rcy-display text-2xl font-extrabold">À quel fournisseur ?</h2>
                    <p class="mt-1 text-slate-500">Choisissez celui qui va recycler vos articles.</p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach($suppliers as $supplier)
                            <button type="button" @click="pickSupplier('{{ $supplier->id }}')"
                                    class="relative flex items-center gap-4 rounded-2xl border-2 p-5 text-left transition"
                                    :class="supplier === '{{ $supplier->id }}' ? 'border-teal-600 bg-teal-50 dark:bg-teal-950/40' : 'border-slate-200 hover:border-teal-300 dark:border-slate-700'">
                                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-teal-700 text-xl font-black text-white">{{ mb_strtoupper(mb_substr($supplier->name, 0, 1)) }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-lg font-black">{{ $supplier->name }}</span>
                                    <span class="block text-sm text-slate-500">{{ $supplier->city ? $supplier->city.', ' : '' }}{{ $supplier->country }}</span>
                                </span>
                                <svg x-show="supplier === '{{ $supplier->id }}'" class="absolute right-4 top-4 h-6 w-6 text-teal-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                            </button>
                        @endforeach
                    </div>
                </section>

                {{-- Étape 2 : articles --}}
                <section x-show="step === 2" x-cloak class="space-y-4">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                        <h2 class="rcy-display text-2xl font-extrabold">Qu’est-ce que vous envoyez ?</h2>
                        <p class="mt-1 text-slate-500">Un « lot » = un type d’articles. Vous pouvez en ajouter plusieurs.</p>
                    </div>

                    <template x-for="(item, index) in items" :key="item.uid">
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
                            <div class="flex items-start justify-between gap-3">
                                <p class="flex min-w-0 items-center gap-2">
                                    <span class="rounded-lg bg-teal-700 px-2 py-1 text-xs font-black text-white" x-text="'Lot ' + (index + 1)"></span>
                                    <span class="truncate text-sm font-semibold text-slate-500" x-text="autoLabel(item)"></span>
                                </p>
                                <button type="button" x-show="items.length > 1" @click="items.splice(index, 1)" class="shrink-0 rounded-lg px-2 py-1 text-sm font-bold text-slate-400 hover:bg-rose-50 hover:text-rose-600">Retirer</button>
                            </div>

                            <p class="mt-5 font-bold">1. C’est quel genre d’articles ?</p>
                            <input type="hidden" :name="'items[' + index + '][kind]'" :value="item.kind">
                            <div class="mt-2 grid grid-cols-3 gap-2 sm:gap-3">
                                @foreach($kinds as $value => [$label, $hint, $icon])
                                    <button type="button" @click="item.kind = '{{ $value }}'"
                                            class="rounded-2xl border-2 p-3 text-center transition sm:p-4"
                                            :class="item.kind === '{{ $value }}' ? 'border-teal-600 bg-teal-50 text-teal-900 dark:bg-teal-950/40 dark:text-teal-100' : 'border-slate-200 text-slate-600 hover:border-teal-300 dark:border-slate-700 dark:text-slate-300'">
                                        <svg class="mx-auto h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
                                        <span class="mt-2 block text-sm font-black sm:text-base">{{ $label }}</span>
                                        <span class="hidden text-xs text-slate-500 sm:block">{{ $hint }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                                <label class="block sm:col-span-2">
                                    <span class="font-bold">2. Quel produit ou quelle matière ?</span>
                                    <select x-model="item.what" class="field !py-3 text-base">
                                        <option value="">Je ne sais pas</option>
                                        @if($products->isNotEmpty())
                                            <optgroup label="Mes produits">
                                                @foreach($products as $product)<option value="p:{{ $product->id }}">{{ $product->name }}</option>@endforeach
                                            </optgroup>
                                        @endif
                                        <optgroup label="Matières">
                                            @foreach($materials as $material)<option value="m:{{ $material->id }}">{{ $material->name }}</option>@endforeach
                                        </optgroup>
                                    </select>
                                    <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.what.startsWith('p:') ? item.what.slice(2) : ''">
                                    <input type="hidden" :name="'items[' + index + '][material_id]'" :value="item.what.startsWith('m:') ? item.what.slice(2) : ''">
                                </label>

                                <div>
                                    <span class="font-bold">3. Combien de kilos ?</span>
                                    <div class="mt-1 flex items-stretch overflow-hidden rounded-xl border border-slate-300 dark:border-slate-600">
                                        <button type="button" @click="bump(item, 'quantity_kg', -1)" class="w-12 shrink-0 bg-slate-50 text-2xl font-bold text-slate-600 hover:bg-teal-50 dark:bg-slate-800 dark:text-slate-200" aria-label="Moins">−</button>
                                        <input type="number" step="0.1" min="0.1" inputmode="decimal" :name="'items[' + index + '][quantity_kg]'" x-model="item.quantity_kg" placeholder="0" class="w-full min-w-0 border-0 bg-transparent text-center text-xl font-black tabular-nums [appearance:textfield] focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                        <span class="grid place-items-center pr-3 text-sm font-bold text-slate-400">kg</span>
                                        <button type="button" @click="bump(item, 'quantity_kg', 1)" class="w-12 shrink-0 bg-slate-50 text-2xl font-bold text-slate-600 hover:bg-teal-50 dark:bg-slate-800 dark:text-slate-200" aria-label="Plus">+</button>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">Une estimation suffit : le fournisseur pèsera à l’arrivée.</p>
                                </div>

                                <div x-show="item.kind === 'defective'">
                                    <span class="font-bold">4. Combien de pièces ?</span>
                                    <div class="mt-1 flex items-stretch overflow-hidden rounded-xl border border-slate-300 dark:border-slate-600">
                                        <button type="button" @click="bump(item, 'pieces', -1)" class="w-12 shrink-0 bg-slate-50 text-2xl font-bold text-slate-600 hover:bg-teal-50 dark:bg-slate-800 dark:text-slate-200" aria-label="Moins">−</button>
                                        <input type="number" min="1" inputmode="numeric" :name="'items[' + index + '][pieces]'" :disabled="item.kind !== 'defective'" x-model="item.pieces" placeholder="0" class="w-full min-w-0 border-0 bg-transparent text-center text-xl font-black tabular-nums [appearance:textfield] focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                        <span class="grid place-items-center pr-3 text-sm font-bold text-slate-400">pièces</span>
                                        <button type="button" @click="bump(item, 'pieces', 1)" class="w-12 shrink-0 bg-slate-50 text-2xl font-bold text-slate-600 hover:bg-teal-50 dark:bg-slate-800 dark:text-slate-200" aria-label="Plus">+</button>
                                    </div>
                                </div>

                                <div class="sm:col-span-2" x-show="item.kind === 'defective'">
                                    <span class="font-bold">5. Quel est le problème ?</span>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($problems as $problem)
                                            <button type="button" @click="toggleProblem(item, @js($problem))"
                                                    class="rounded-full border px-3.5 py-2 text-sm font-semibold transition"
                                                    :class="item.issue.includes(@js($problem)) ? 'border-teal-600 bg-teal-700 text-white' : 'border-slate-200 text-slate-700 hover:border-teal-300 dark:border-slate-700 dark:text-slate-200'">{{ $problem }}</button>
                                        @endforeach
                                    </div>
                                    <input :name="'items[' + index + '][issue]'" :disabled="item.kind !== 'defective'" x-model="item.issue" maxlength="500" class="field mt-2" placeholder="Ou écrivez-le avec vos mots">
                                </div>

                                <label class="flex cursor-pointer items-center gap-4 rounded-2xl border-2 border-dashed border-teal-300 p-4 transition hover:bg-teal-50/60 dark:border-teal-800 dark:hover:bg-teal-950/30 sm:col-span-2">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-200">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h2l2-2h6l2 2h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold" x-text="item.photoName || 'Ajouter une photo (conseillé)'"></span>
                                        <span class="block text-sm text-slate-500" x-text="item.photoName ? 'Photo prête. Cliquez pour la changer.' : 'L’IA repère les défauts visibles et les montre au fournisseur.'"></span>
                                    </span>
                                    <input type="file" :name="'items[' + index + '][photo]'" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="item.photoName = $event.target.files[0]?.name || ''">
                                </label>
                            </div>
                        </div>
                    </template>

                    <button type="button" x-show="items.length < 10" @click="add()" class="flex w-full items-center justify-center gap-2 rounded-3xl border-2 border-dashed border-slate-300 p-5 font-bold text-slate-600 transition hover:border-teal-400 hover:text-teal-800 dark:border-slate-700 dark:text-slate-300">
                        <span class="text-2xl leading-none">+</span> Ajouter un autre lot
                    </button>
                </section>

                {{-- Étape 3 : ce que la marque veut en retour --}}
                <section x-show="step === 3" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                    <h2 class="rcy-display text-2xl font-extrabold">Que voulez-vous en retour ?</h2>
                    <p class="mt-1 text-slate-500">Le prix est calculé automatiquement. Vous n’avez rien à négocier.</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <button type="button" @click="pickMode('recover')" class="relative flex flex-col items-stretch justify-start rounded-3xl border-2 p-6 text-left transition"
                                :class="mode === 'recover' ? 'border-teal-600 bg-teal-50 dark:bg-teal-950/40' : 'border-slate-200 hover:border-teal-300 dark:border-slate-700'">
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-teal-700 text-white">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                            </span>
                            <span class="mt-4 block text-xl font-black">Je récupère la matière</span>
                            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Le fournisseur vous renvoie la matière recyclée. Vous payez seulement les frais de recyclage.</span>
                            <span class="mt-5 block rounded-2xl bg-white p-4 ring-1 ring-teal-100 dark:bg-slate-900 dark:ring-teal-900">
                                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Vous payez environ</span>
                                <span class="rcy-display block text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white" x-text="money(quote('recover').fees)"></span>
                                <span class="mt-1 block text-sm font-bold text-teal-700 dark:text-teal-300" x-show="quote('recover').savings > 0" x-text="'Vous économisez ≈ ' + money(quote('recover').savings) + ' par rapport au neuf'"></span>
                            </span>
                            <svg x-show="mode === 'recover'" class="absolute right-5 top-5 h-7 w-7 text-teal-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                        </button>
                        <button type="button" @click="pickMode('buyback')" class="relative flex flex-col items-stretch justify-start rounded-3xl border-2 p-6 text-left transition"
                                :class="mode === 'buyback' ? 'border-teal-600 bg-teal-50 dark:bg-teal-950/40' : 'border-slate-200 hover:border-teal-300 dark:border-slate-700'">
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-teal-700 text-white">
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="M15.4 10.2A6 6 0 1 1 9.8 15.4"/><path d="M9 6.5v5M7.2 8h3"/></svg>
                            </span>
                            <span class="mt-4 block text-xl font-black">Le fournisseur la rachète</span>
                            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">Vous n’avez pas besoin de la matière : le fournisseur la garde et vous verse un avoir.</span>
                            <span class="mt-5 block rounded-2xl bg-white p-4 ring-1 ring-teal-100 dark:bg-slate-900 dark:ring-teal-900">
                                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Vous recevez environ</span>
                                <span class="rcy-display block text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white" x-text="money(quote('buyback').credit)"></span>
                                <span class="mt-1 block text-sm text-slate-500">Frais de recyclage déjà déduits</span>
                            </span>
                            <svg x-show="mode === 'buyback'" class="absolute right-5 top-5 h-7 w-7 text-teal-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>

                    <details class="mt-6 rounded-2xl border border-slate-200 dark:border-slate-700" @if(old('brand_message') || old('wished_pickup_at')) open @endif>
                        <summary class="cursor-pointer px-5 py-4 font-bold text-slate-600 dark:text-slate-300">Ajouter un message ou une date (facultatif)</summary>
                        <div class="grid gap-4 px-5 pb-5 sm:grid-cols-[1fr_200px]">
                            <label class="block text-sm font-bold">Message au fournisseur
                                <textarea name="brand_message" rows="3" class="field" placeholder="Ex. Nous voulons ce fil pour la collection d’été.">{{ old('brand_message') }}</textarea>
                            </label>
                            <label class="block text-sm font-bold">Enlèvement souhaité
                                <input type="date" name="wished_pickup_at" value="{{ old('wished_pickup_at') }}" min="{{ now()->toDateString() }}" class="field">
                            </label>
                        </div>
                    </details>
                </section>

                {{-- Étape 4 : vérifier et envoyer --}}
                <section x-show="step === 4" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                    <h2 class="rcy-display text-2xl font-extrabold">Vérifiez et envoyez</h2>
                    <p class="mt-1 text-slate-500">Tout est juste ? Un clic et le fournisseur est prévenu.</p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                        <div class="rounded-2xl bg-slate-50 p-5 dark:bg-slate-800/60">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Vous envoyez</p>
                            <p class="rcy-display mt-1 text-3xl font-extrabold tabular-nums" x-text="format(totalKg()) + ' kg'"></p>
                            <p class="text-sm text-slate-500" x-text="'à ' + (supplierNames[supplier] || '…')"></p>
                        </div>
                        <svg class="mx-auto h-8 w-8 rotate-90 text-teal-500 sm:rotate-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                        <div class="rounded-2xl bg-teal-50 p-5 dark:bg-teal-950/40">
                            <p class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-300">Il en sortira environ</p>
                            <p class="rcy-display mt-1 text-3xl font-extrabold tabular-nums text-teal-900 dark:text-teal-100" x-text="format(estimate().kg) + ' kg'"></p>
                            <p class="text-sm text-teal-800 dark:text-teal-200" x-text="'de ' + estimate().name.toLowerCase()"></p>
                        </div>
                    </div>

                    <ul class="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200 dark:divide-slate-800 dark:border-slate-700">
                        <template x-for="(item, index) in items" :key="'r' + item.uid">
                            <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span class="min-w-0 truncate"><span class="mr-2 rounded-md bg-teal-700 px-1.5 py-0.5 text-[11px] font-black text-white" x-text="'L' + (index + 1)"></span><span x-text="autoLabel(item)"></span></span>
                                <span class="shrink-0 font-bold tabular-nums" x-text="format(item.quantity_kg) + ' kg'"></span>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-6 rounded-2xl border border-teal-200 p-5 dark:border-teal-900">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="font-black" x-text="mode === 'buyback' ? 'Le fournisseur rachète la matière' : 'Vous récupérez la matière'"></p>
                            <button type="button" @click="go(3)" class="text-sm font-bold text-teal-700 hover:underline dark:text-teal-300">Changer</button>
                        </div>
                        <dl class="mt-4 space-y-2 text-sm">
                            <template x-for="line in quote(mode).lines" :key="line.label">
                                <div class="flex justify-between gap-3"><dt class="text-slate-600 dark:text-slate-300" x-text="line.label"></dt><dd class="font-semibold tabular-nums" x-text="money(line.amount)"></dd></div>
                            </template>
                            <div class="flex justify-between gap-3 border-t border-slate-100 pt-2 dark:border-slate-800"><dt class="font-bold">Frais de recyclage</dt><dd class="font-bold tabular-nums" x-text="money(quote(mode).fees)"></dd></div>
                            <template x-if="mode === 'buyback'">
                                <div class="flex justify-between gap-3"><dt class="text-slate-600 dark:text-slate-300">Rachat de la matière</dt><dd class="font-semibold tabular-nums text-teal-700" x-text="'+ ' + money(quote(mode).buyValue)"></dd></div>
                            </template>
                        </dl>
                        <div class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-teal-700 px-5 py-4 text-white">
                            <span class="font-bold" x-text="mode === 'buyback' ? 'Vous recevez' : 'Vous payez'"></span>
                            <span class="rcy-display text-2xl font-extrabold tabular-nums" x-text="money(mode === 'buyback' ? quote(mode).credit : quote(mode).fees)"></span>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">Prix calculé automatiquement selon le barème de la plateforme. Il est recalculé sur le poids réellement pesé chez le fournisseur.</p>
                    </div>
                </section>

                {{-- Navigation --}}
                {{-- mb-24 : le bouton reste au-dessus de l'assistant flottant sur mobile. --}}
                <div class="mb-24 mt-6 flex items-center justify-between gap-3 lg:mb-0">
                    <button type="button" x-show="step > 1" @click="back()" class="rounded-2xl px-5 py-4 font-bold text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800">← Retour</button>
                    <span x-show="step === 1"></span>
                    <button type="button" x-show="step < 4" @click="next()" class="ml-auto inline-flex items-center gap-2 rounded-2xl bg-teal-700 px-8 py-4 text-lg font-black text-white shadow-sm hover:bg-teal-800">Continuer →</button>
                    <button type="submit" x-show="step === 4" x-cloak :disabled="sending" class="ml-auto inline-flex items-center gap-2 rounded-2xl bg-teal-700 px-8 py-4 text-lg font-black text-white shadow-sm hover:bg-teal-800 disabled:opacity-60">
                        <span x-text="sending ? 'Envoi en cours…' : 'Envoyer la demande'">Envoyer la demande</span>
                    </button>
                </div>
            </div>

            {{-- Résumé toujours visible --}}
            <aside class="hidden lg:block lg:sticky lg:top-24 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-teal-200 bg-white dark:border-teal-900 dark:bg-slate-900">
                    <div class="bg-teal-800 p-5 text-white">
                        <p class="text-xs font-bold uppercase tracking-wider text-teal-200">Votre demande</p>
                        <p class="mt-2 truncate text-lg font-black" x-text="supplierNames[supplier] || 'Fournisseur à choisir'"></p>
                    </div>
                    <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                        <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-slate-500">Articles</dt><dd class="font-bold tabular-nums" x-text="format(totalKg()) + ' kg'"></dd></div>
                        <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-slate-500">Matière obtenue</dt><dd class="text-right font-bold tabular-nums" x-text="totalKg() ? '≈ ' + format(estimate().kg) + ' kg' : '—'"></dd></div>
                        <div class="flex justify-between gap-3 px-5 py-3"><dt class="text-slate-500">Retour</dt><dd class="font-bold" x-text="mode === 'buyback' ? 'Rachat' : (mode === 'recover' ? 'Récupération' : '—')"></dd></div>
                        <div class="flex justify-between gap-3 bg-teal-50 px-5 py-4 dark:bg-teal-950/40">
                            <dt class="font-bold text-teal-900 dark:text-teal-100" x-text="mode === 'buyback' ? 'Vous recevez' : 'Vous payez'"></dt>
                            <dd class="rcy-display text-xl font-extrabold tabular-nums text-teal-900 dark:text-teal-100" x-text="mode ? money(mode === 'buyback' ? quote(mode).credit : quote(mode).fees) : '—'"></dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </form>

    <script>
        function recyclingWizard(config) {
            const shortKinds = { defective: 'Défectueux', surplus: 'Surplus', offcuts: 'Chutes' };
            const round = (value, digits = 2) => Math.round(value * 10 ** digits) / 10 ** digits;
            return {
                ...config, sending: false, hint: '', reached: config.step, next_uid: config.items.length + 1,
                autoLabel(item) {
                    const [type, id] = (item.what || ':').split(':');
                    const what = (type === 'p' ? this.productNames[id] : this.materialNames[id]) || 'matière à préciser';
                    return [shortKinds[item.kind], what, item.kind === 'defective' && item.pieces ? item.pieces + ' pièces' : null].filter(Boolean).join(' · ');
                },
                add() { this.items.push({ uid: this.next_uid++, kind: 'surplus', what: '', pieces: '', quantity_kg: '', issue: '', photoName: '' }); },
                bump(item, field, delta) { item[field] = Math.max(0, (Number(item[field]) || 0) + delta) || ''; },
                toggleProblem(item, problem) {
                    const parts = item.issue.split(',').map((part) => part.trim()).filter(Boolean);
                    const at = parts.indexOf(problem);
                    at >= 0 ? parts.splice(at, 1) : parts.push(problem);
                    item.issue = parts.join(', ');
                },
                pickSupplier(id) { this.supplier = id; setTimeout(() => this.next(), 180); },
                pickMode(mode) { this.mode = mode; this.hint = ''; },
                check(step) {
                    if (step === 1 && ! this.supplier) return 'Choisissez un fournisseur pour continuer.';
                    if (step === 2) {
                        if (this.items.some((item) => ! (Number(item.quantity_kg) > 0))) return 'Indiquez le poids (en kg) de chaque lot.';
                        if (this.items.some((item) => item.kind === 'defective' && ! item.issue.trim())) return 'Dites-nous le problème des pièces ratées (touchez une étiquette).';
                    }
                    if (step === 3 && ! this.mode) return 'Choisissez ce que vous voulez en retour.';
                    return '';
                },
                next() {
                    this.hint = this.check(this.step);
                    if (this.hint) return;
                    this.step++; this.reached = Math.max(this.reached, this.step);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                back() { this.hint = ''; this.step--; },
                go(step) { if (step <= this.reached) { this.hint = ''; this.step = step; } },
                totalKg() { return this.items.reduce((sum, item) => sum + (Number(item.quantity_kg) || 0), 0); },
                defectivePieces() { return this.items.reduce((sum, item) => sum + (item.kind === 'defective' ? Number(item.pieces) || 0 : 0), 0); },
                format(value) { return Number(value || 0).toLocaleString('fr-FR', { maximumFractionDigits: 1 }); },
                money(value) { return Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TND'; },
                estimate() {
                    const byOutput = {};
                    let input = 0, output = 0;
                    this.items.forEach((item) => {
                        const kg = Number(item.quantity_kg) || 0;
                        const [type, id] = (item.what || ':').split(':');
                        const materialId = type === 'm' ? id : (type === 'p' ? this.productMaterial[id] : null);
                        const [name, rate] = this.routes[materialId] || ['Coton recyclé', 0.6];
                        byOutput[name] = (byOutput[name] || 0) + kg;
                        input += kg; output += kg * rate * (this.kindFactors[item.kind] || 0.85);
                    });
                    const name = Object.keys(byOutput).sort((a, b) => byOutput[b] - byOutput[a])[0] || 'Coton recyclé';
                    return { name, kg: round(output, 1), yield: input ? Math.round(output / input * 100) : 0 };
                },
                // Même barème que le serveur (App\Services\RecyclingPricing).
                quote(mode) {
                    const t = this.tariff, est = this.estimate(), kg = this.totalKg(), pieces = this.defectivePieces();
                    const lines = [{ label: 'Collecte et transport', amount: round(t.collection_flat + t.collection_per_kg * kg) }];
                    if (pieces) lines.push({ label: 'Démontage des pièces', amount: round(pieces * t.dismantling_per_piece) });
                    lines.push({ label: 'Recyclage', amount: round(kg * (t.recycling_per_kg[est.name] ?? t.defaults.recycling)) });
                    const fees = round(lines.reduce((sum, line) => sum + line.amount, 0));
                    const buyValue = round(est.kg * (t.recycled_price[est.name] ?? t.defaults.recycled_price) * t.buyback_share);
                    const newValue = round(est.kg * (t.new_price[est.name] ?? t.defaults.new_price));
                    return { lines, fees, buyValue, savings: Math.max(0, round(newValue - fees)), credit: Math.max(0, round(buyValue - fees)) };
                },
            };
        }
    </script>
    @endif
</x-brand-layout>
