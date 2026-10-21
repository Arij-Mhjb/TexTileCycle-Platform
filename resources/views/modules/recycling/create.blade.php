@php
$initialItems = collect(old('items', [['kind' => 'defective', 'product_id' => '', 'material_id' => '', 'pieces' => '', 'quantity_kg' => '', 'issue' => '']]))
    ->values()->map(fn ($item, $i) => ['uid' => $i + 1] + array_merge(['kind' => 'defective', 'product_id' => '', 'material_id' => '', 'pieces' => '', 'quantity_kg' => '', 'issue' => ''], $item));
$kinds = [
    'defective' => ['Pièces défectueuses', 'Couture ratée, tache, mauvaise taille…'],
    'surplus' => ['Surplus de matière', 'Rouleaux ou fils non utilisés'],
    'offcuts' => ['Chutes de coupe', 'Restes de découpe en atelier'],
];
@endphp
<x-brand-layout title="Nouvelle demande de recyclage">
    @include('modules.recycling._header', [
        'eyebrow' => 'Nouvelle demande',
        'title' => 'Confier des articles à recycler',
        'subtitle' => 'Dites à votre fournisseur ce que vous voulez lui envoyer. Il vous proposera la matière recyclée qu’il peut en tirer, et s’il vous la rend ou vous la revend.',
    ])

    @if($suppliers->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
            Aucun de vos fournisseurs n’a encore activé son espace. Donnez-lui l’accès depuis sa fiche (« Donner l’accès »), puis revenez ici.
        </div>
    @else
    <form method="POST" action="{{ route('brand.recycling.store') }}" enctype="multipart/form-data"
          x-data="recyclingRequest(@js($initialItems), @js($routes), @js($productMaterial), @js($kindFactors), @js((string) old('supplier_id', $selectedSupplier)), @js($products->pluck('name', 'id')), @js($materials->pluck('name', 'id')))"
          @submit="if (sending) { $event.preventDefault() } sending = true"
          class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        @csrf
        <div class="space-y-6">
            @if($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><p class="font-bold">Vérifiez le formulaire :</p><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            {{-- 1. Fournisseur --}}
            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="flex items-center gap-3 text-lg font-black"><span class="grid h-8 w-8 place-items-center rounded-xl bg-teal-700 text-sm text-white">1</span> À quel fournisseur ?</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($suppliers as $supplier)
                        <label class="relative cursor-pointer rounded-2xl border p-4 transition" :class="supplier === '{{ $supplier->id }}' ? 'border-teal-600 bg-teal-50/70 ring-1 ring-teal-600 dark:bg-teal-950/40' : 'border-slate-200 hover:border-teal-300 dark:border-slate-700'">
                            <input type="radio" name="supplier_id" value="{{ $supplier->id }}" x-model="supplier" class="sr-only" required>
                            <span class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-teal-100 font-black text-teal-800 dark:bg-teal-900 dark:text-teal-100">{{ mb_strtoupper(mb_substr($supplier->name, 0, 1)) }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate font-bold">{{ $supplier->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $supplier->city ? $supplier->city.', ' : '' }}{{ $supplier->country }}@if($supplier->compliance_score !== null) · conformité {{ $supplier->compliance_score }} %@endif</span>
                                </span>
                            </span>
                            <svg x-show="supplier === '{{ $supplier->id }}'" class="absolute right-3 top-3 h-5 w-5 text-teal-700" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- 2. Articles --}}
            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="flex items-center gap-3 text-lg font-black"><span class="grid h-8 w-8 place-items-center rounded-xl bg-teal-700 text-sm text-white">2</span> Qu’envoyez-vous ?</h2>
                    <span class="text-sm text-slate-500" x-text="items.length + ' lot(s)'"></span>
                </div>

                <div class="mt-5 space-y-4">
                    <template x-for="(item, index) in items" :key="item.uid">
                        <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-black text-teal-800 dark:text-teal-300" x-text="'Lot ' + (index + 1)"></p>
                                <button type="button" x-show="items.length > 1" @click="items.splice(index, 1)" class="text-xs font-bold text-slate-400 hover:text-rose-600">Retirer</button>
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3" role="radiogroup">
                                @foreach($kinds as $value => [$label, $hint])
                                    <label class="cursor-pointer rounded-xl border px-3 py-2.5 text-sm transition" :class="item.kind === '{{ $value }}' ? 'border-teal-600 bg-teal-50 dark:bg-teal-950/40' : 'border-slate-200 hover:border-teal-300 dark:border-slate-700'">
                                        <input type="radio" :name="'items[' + index + '][kind]'" value="{{ $value }}" x-model="item.kind" class="sr-only">
                                        <span class="block font-bold">{{ $label }}</span><span class="block text-xs text-slate-500">{{ $hint }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <p class="text-sm font-bold">Nom du lot <span class="font-normal text-slate-400">· généré automatiquement</span></p>
                                    <p class="mt-1 flex items-center gap-2 rounded-xl bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-900 dark:bg-teal-950/50 dark:text-teal-100">
                                        <span class="rounded-md bg-teal-700 px-1.5 py-0.5 text-[11px] font-black text-white" x-text="'L' + (index + 1)"></span>
                                        <span x-text="autoLabel(item)"></span>
                                    </p>
                                </div>
                                <label class="block text-sm font-bold" x-show="item.kind === 'defective'">Produit concerné
                                    <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" class="field">
                                        <option value="">— aucun —</option>
                                        @foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach
                                    </select>
                                </label>
                                <label class="block text-sm font-bold" x-show="item.kind !== 'defective' || ! item.product_id">Matière principale
                                    <select :name="'items[' + index + '][material_id]'" x-model="item.material_id" class="field">
                                        <option value="">— à préciser —</option>
                                        @foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach
                                    </select>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="block text-sm font-bold">Poids (kg) <span class="text-rose-500">*</span>
                                        <input type="number" step="0.1" min="0.1" :name="'items[' + index + '][quantity_kg]'" x-model.number="item.quantity_kg" required class="field">
                                    </label>
                                    <label class="block text-sm font-bold">Pièces
                                        <input type="number" min="1" :name="'items[' + index + '][pieces]'" x-model.number="item.pieces" class="field" placeholder="—">
                                    </label>
                                </div>
                                <label class="block text-sm font-bold sm:col-span-2" x-show="item.kind === 'defective'">Quel est le problème ? <span class="text-rose-500">*</span>
                                    <input :name="'items[' + index + '][issue]'" x-model="item.issue" :required="item.kind === 'defective'" maxlength="500" class="field" placeholder="Ex. fermeture mal cousue sur 25 pièces">
                                </label>
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-teal-300 px-4 py-3 text-sm dark:border-teal-800 sm:col-span-2">
                                    <svg class="h-5 w-5 shrink-0 text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M3 8a2 2 0 0 1 2-2h2l2-2h6l2 2h2a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                                    <span class="min-w-0 flex-1"><span class="block font-bold">Photo (conseillée)</span><span class="block text-xs text-slate-500">L’IA repère les défauts visibles pour le fournisseur.</span></span>
                                    <input type="file" :name="'items[' + index + '][photo]'" accept="image/jpeg,image/png,image/webp" class="max-w-[11rem] text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-teal-50 file:px-2 file:py-1 file:font-bold file:text-teal-800">
                                </label>
                            </div>
                        </div>
                    </template>
                </div>
                <button type="button" x-show="items.length < 10" @click="add()" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-teal-200 px-4 py-2.5 text-sm font-bold text-teal-800 hover:bg-teal-50 dark:border-teal-800 dark:text-teal-200">+ Ajouter un lot</button>
            </section>

            {{-- 3. Message --}}
            <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="flex items-center gap-3 text-lg font-black"><span class="grid h-8 w-8 place-items-center rounded-xl bg-teal-700 text-sm text-white">3</span> Un mot pour le fournisseur</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_220px]">
                    <label class="block text-sm font-bold">Message
                        <textarea name="brand_message" rows="3" class="field" placeholder="Ex. Nous aimerions récupérer le fil recyclé pour la collection d’été.">{{ old('brand_message') }}</textarea>
                    </label>
                    <label class="block text-sm font-bold">Enlèvement souhaité
                        <input type="date" name="wished_pickup_at" value="{{ old('wished_pickup_at') }}" min="{{ now()->toDateString() }}" class="field">
                    </label>
                </div>
            </section>
        </div>

        {{-- Récapitulatif et estimation en direct --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="overflow-hidden rounded-3xl border border-teal-200 bg-white dark:border-teal-900 dark:bg-slate-900">
                <div class="bg-teal-800 p-5 text-white">
                    <p class="text-xs font-bold uppercase tracking-wider text-teal-200">Récapitulatif</p>
                    <p class="rcy-display mt-2 text-4xl font-extrabold tabular-nums" x-text="format(totalKg()) + ' kg'"></p>
                    <p class="text-sm text-teal-100" x-text="items.length + ' lot(s)' + (totalPieces() ? ' · ' + totalPieces() + ' pièces' : '')"></p>
                </div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Estimation</p>
                    <div class="mt-3 flex items-center gap-3">
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-bold tabular-nums dark:bg-slate-800" x-text="format(totalKg()) + ' kg'"></span>
                        <svg class="h-4 w-4 text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                        <span class="rounded-lg bg-teal-50 px-2.5 py-1 text-sm font-black tabular-nums text-teal-800 dark:bg-teal-950 dark:text-teal-200" x-text="'≈ ' + format(estimate().kg) + ' kg'"></span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">de <strong x-text="estimate().name"></strong> <span class="text-slate-400" x-text="totalKg() ? '(rendement ≈ ' + estimate().yield + ' %)' : ''"></span></p>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Estimation selon la matière et l’état des articles. Le fournisseur confirmera dans sa proposition.</p>
                    <x-module.button module="loop" class="mt-5 w-full !py-3 disabled:opacity-60" x-bind:disabled="sending || ! supplier">
                        <span x-text="sending ? 'Envoi et analyse des photos…' : 'Envoyer la demande'">Envoyer la demande</span>
                    </x-module.button>
                    <p class="mt-2 text-center text-xs text-slate-400" x-show="! supplier">Choisissez d’abord un fournisseur.</p>
                </div>
            </div>
        </aside>
    </form>

    <script>
        function recyclingRequest(items, routes, productMaterial, kindFactors, supplier, productNames, materialNames) {
            const shortKinds = { defective: 'Défectueux', surplus: 'Surplus', offcuts: 'Chutes' };
            return {
                items, supplier, sending: false, next: items.length + 1,
                autoLabel(item) {
                    const what = (item.kind === 'defective' && productNames[item.product_id]) || materialNames[item.material_id] || 'matière à préciser';
                    return [shortKinds[item.kind], what, item.pieces ? item.pieces + ' pièces' : null].filter(Boolean).join(' · ');
                },
                add() { this.items.push({ uid: this.next++, kind: 'surplus', product_id: '', material_id: '', pieces: '', quantity_kg: '', issue: '' }); },
                totalKg() { return this.items.reduce((sum, item) => sum + (Number(item.quantity_kg) || 0), 0); },
                totalPieces() { return this.items.reduce((sum, item) => sum + (Number(item.pieces) || 0), 0); },
                format(value) { return Number(value || 0).toLocaleString('fr-FR', { maximumFractionDigits: 1 }); },
                estimate() {
                    const byOutput = {};
                    let input = 0, output = 0;
                    this.items.forEach((item) => {
                        const kg = Number(item.quantity_kg) || 0;
                        const materialId = item.material_id || (item.kind === 'defective' ? productMaterial[item.product_id] : null);
                        const [name, rate] = routes[materialId] || ['Coton recyclé', 0.6];
                        const out = kg * rate * (kindFactors[item.kind] || 0.85);
                        byOutput[name] = (byOutput[name] || 0) + kg;
                        input += kg; output += out;
                    });
                    const name = Object.keys(byOutput).sort((a, b) => byOutput[b] - byOutput[a])[0] || 'matière recyclée';
                    return { name, kg: output, yield: input ? Math.round(output / input * 100) : 0 };
                },
            };
        }
    </script>
    @endif
</x-brand-layout>
