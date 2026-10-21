@php
$layout = ['brand' => 'brand-layout', 'supplier' => 'module.supplier-layout', 'admin' => 'app-layout'][$space];
$prefix = ['brand' => 'brand.supplier-returns', 'supplier' => 'supplier.returns', 'admin' => 'admin.supplier-returns'][$space];
$kg = fn ($value) => number_format((float) $value, 2, ',', ' ').' kg';
$ai = $return->ai_analysis;
$card = 'shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900';
@endphp
<x-dynamic-component :component="$layout" :title="$return->reference">
    <x-module.hero module="repair" :eyebrow="'Retour fournisseur · '.$return->reasonLabel()" :title="$kg($return->quantity_kg).' de '.($return->material?->name ?? 'matière')"
        :description="$return->reference.' · '.$return->brand->name.' → '.$return->supplier->name.' · déclaré le '.$return->created_at->format('d/m/Y')">
        <x-slot:actions>
            <x-module.badge :tone="$return->statusTone()" class="!bg-white/95">{{ $return->statusLabel() }}</x-module.badge>
            <x-module.button module="repair" variant="light" :href="route($prefix.'.index')">← Tous les retours</x-module.button>
        </x-slot:actions>
    </x-module.hero>

    <div class="{{ $card }} mb-6">@include('modules.supplier-returns._timeline', ['return' => $return])</div>

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="{{ $card }}">
                <h2 class="text-lg font-black">Déclaration de la marque</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Lot</dt><dd class="font-bold">{{ $return->batch?->batch_code }} · {{ $return->batch?->origin_country }}</dd></div>
                    <div><dt class="text-slate-500">Commande</dt><dd class="font-bold">{{ $return->order?->reference ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Motif</dt><dd class="font-bold">{{ $return->reasonLabel() }}</dd></div>
                    <div><dt class="text-slate-500">Quantité</dt><dd class="font-bold">{{ $kg($return->quantity_kg) }} sur {{ $kg($return->batch?->quantity_kg) }} reçus</dd></div>
                </dl>
                @if($return->description)<p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">« {{ $return->description }} »</p>@endif

                @if($return->photo_path)
                    <div class="mt-5 grid gap-5 sm:grid-cols-5">
                        <a href="{{ route($prefix.'.document', [$return, 'photo']) }}" target="_blank" class="block overflow-hidden rounded-xl bg-slate-100 sm:col-span-2">
                            <img src="{{ route($prefix.'.document', [$return, 'photo']) }}" alt="Photo jointe par la marque" class="h-48 w-full object-cover">
                        </a>
                        <div class="sm:col-span-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-module.ai-badge :source="$ai['source'] ?? 'none'"/>
                                @if(isset($ai['consistent']) && $ai['consistent'] !== null)
                                    <x-module.badge :tone="$ai['consistent'] ? 'good' : 'warn'">{{ $ai['consistent'] ? 'La photo confirme le motif' : 'La photo ne confirme pas le motif' }}</x-module.badge>
                                @endif
                            </div>
                            <p class="mt-3 text-sm font-semibold">{{ $ai['summary'] ?? 'Analyse IA indisponible.' }}</p>
                            <div class="mt-3 space-y-2">
                                @foreach($ai['findings'] ?? [] as $finding)
                                    <x-module.bar module="repair" :label="$finding['label']" :value="$finding['confidence']" :display="$finding['confidence'].' %'"/>
                                @endforeach
                            </div>
                            @if(! empty($ai['model']))<p class="mt-3 text-xs text-slate-400">Modèle : {{ $ai['model'] }}</p>@endif
                        </div>
                    </div>
                @endif
            </section>

            @if($return->destination)
                <section class="{{ $card }}">
                    <h2 class="text-lg font-black">Reprise par le fournisseur</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Seconde vie prévue : <strong>{{ $return->destinationLabel() }}</strong>@if($return->pickup_at) · enlèvement le {{ $return->pickup_at->format('d/m/Y') }}@endif</p>
                    @if($return->supplier_note)<p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm dark:bg-slate-800">« {{ $return->supplier_note }} »</p>@endif
                </section>
            @endif

            @if(in_array($return->status, ['completed', 'correction', 'verified'], true))
                <section class="{{ $card }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black">Seconde vie de la matière</h2>
                            <p class="text-sm text-slate-500">{{ $kg($return->savedKg()) }} sauvés du déchet sur {{ $kg($return->quantity_kg) }} ({{ $return->quantity_kg > 0 ? (int) round($return->savedKg() / $return->quantity_kg * 100) : 0 }} %)</p>
                        </div>
                        @if($return->proof_path)<x-module.button module="repair" variant="soft" :href="route($prefix.'.document', [$return, 'proof'])" target="_blank">Voir la preuve</x-module.button>@endif
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach(['resold_kg' => 'Revendu à une autre marque', 'recycled_kg' => 'Recyclé en nouvelle fibre', 'upcycled_kg' => 'Surcyclé', 'waste_kg' => 'Non valorisé'] as $field => $label)
                            <x-module.bar module="repair" :label="$label" :value="(float) $return->{$field}" :max="(float) $return->quantity_kg" :display="$kg($return->{$field})"/>
                        @endforeach
                    </div>
                    @if($return->admin_note)
                        <p class="mt-5 rounded-xl p-4 text-sm {{ $return->status === 'correction' ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800' }}"><strong>TexTileCycle :</strong> {{ $return->admin_note }}</p>
                    @endif
                    @if($return->status === 'verified')<p class="mt-4 text-sm font-semibold text-emerald-700">Vérifié par TexTileCycle le {{ $return->verified_at->format('d/m/Y') }}.</p>@endif
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            {{-- Marque --}}
            @if($space === 'brand' && $return->status === 'accepted')
                <section class="{{ $card }}">
                    <h2 class="font-black">À vous : renvoyer la matière</h2>
                    <p class="mt-2 text-sm text-slate-500">Le fournisseur a accepté la reprise. Confirmez quand la matière est partie.</p>
                    <form method="POST" action="{{ route('brand.supplier-returns.ship', $return) }}" class="mt-4">@csrf<x-module.button module="repair" class="w-full">J’ai renvoyé la matière</x-module.button></form>
                </section>
            @endif

            {{-- Fournisseur --}}
            @if($space === 'supplier' && $return->status === 'requested')
                <form method="POST" action="{{ route('supplier.returns.accept', $return) }}" class="{{ $card }} space-y-4">
                    @csrf
                    <h2 class="font-black">Reprendre la matière</h2>
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-bold">Seconde vie prévue <span class="text-rose-500">*</span></legend>
                        @foreach(\App\Models\SupplierReturn::DESTINATIONS as $value => $label)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 dark:border-slate-700">
                                <input type="radio" name="destination" value="{{ $value }}" @checked(old('destination', $return->reason === 'defect' ? 'recycling' : 'resale') === $value) class="mt-0.5 text-amber-600">
                                <span class="font-semibold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    <x-module.field name="pickup_at" type="date" label="Date d’enlèvement (facultatif)" :min="now()->toDateString()"/>
                    <label class="block"><span class="text-sm font-bold">Message à la marque</span><textarea name="supplier_note" rows="2" class="field" placeholder="Ex. notre transporteur passe mardi matin.">{{ old('supplier_note') }}</textarea></label>
                    <x-module.button module="repair" class="w-full">Accepter la reprise</x-module.button>
                </form>
            @endif

            @if($space === 'supplier' && $return->status === 'in_transit')
                <section class="{{ $card }}">
                    <h2 class="font-black">Matière en route</h2>
                    <p class="mt-2 text-sm text-slate-500">La marque a renvoyé {{ $kg($return->quantity_kg) }}. Confirmez à l’arrivée.</p>
                    <form method="POST" action="{{ route('supplier.returns.receive', $return) }}" class="mt-4">@csrf<x-module.button module="repair" class="w-full">Confirmer la réception</x-module.button></form>
                </section>
            @endif

            @if($space === 'supplier' && in_array($return->status, ['received', 'correction'], true))
                <form method="POST" action="{{ route('supplier.returns.complete', $return) }}" enctype="multipart/form-data" class="{{ $card }} space-y-4"
                      x-data="{ total: {{ (float) $return->quantity_kg }}, values: { resold_kg: {{ (float) old('resold_kg', $return->resold_kg ?? ($return->destination === 'resale' ? $return->quantity_kg : 0)) }}, recycled_kg: {{ (float) old('recycled_kg', $return->recycled_kg ?? ($return->destination === 'recycling' ? $return->quantity_kg : 0)) }}, upcycled_kg: {{ (float) old('upcycled_kg', $return->upcycled_kg ?? ($return->destination === 'upcycling' ? $return->quantity_kg : 0)) }}, waste_kg: {{ (float) old('waste_kg', $return->waste_kg ?? 0) }} },
                               sum() { return Object.values(this.values).reduce((a, b) => a + Number(b || 0), 0) } }">
                    @csrf
                    <h2 class="font-black">Déclarer la seconde vie</h2>
                    <p class="text-sm text-slate-500">Répartissez les {{ $kg($return->quantity_kg) }} reçus.</p>
                    @foreach(['resold_kg' => 'Revendu (kg)', 'recycled_kg' => 'Recyclé (kg)', 'upcycled_kg' => 'Surcyclé (kg)', 'waste_kg' => 'Non valorisé (kg)'] as $field => $label)
                        <label class="block text-sm font-bold">{{ $label }}<input type="number" name="{{ $field }}" step="0.01" min="0" x-model.number="values.{{ $field }}" class="field" required></label>
                    @endforeach
                    <p class="text-sm font-bold" :class="Math.abs(sum() - total) > total * 0.05 ? 'text-rose-600' : 'text-emerald-700'"
                       x-text="'Total : ' + sum().toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' kg sur ' + total.toLocaleString('fr-FR') + ' kg'"></p>
                    <label class="block text-sm font-bold">Preuve <span class="text-rose-500">*</span>
                        <span class="block text-xs font-normal text-slate-500">Bon de recyclage, facture de revente, photo des produits surcyclés (image ou PDF).</span>
                        <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required class="mt-2 block w-full text-sm">
                    </label>
                    <label class="block text-sm font-bold">Commentaire<textarea name="supplier_note" rows="2" class="field">{{ old('supplier_note') }}</textarea></label>
                    <x-module.button module="repair" class="w-full">Envoyer la déclaration</x-module.button>
                </form>
            @endif

            {{-- Admin --}}
            @if($space === 'admin' && $return->status === 'completed')
                <form method="POST" action="{{ route('admin.supplier-returns.verify', $return) }}" class="{{ $card }} space-y-4">
                    @csrf @method('PATCH')
                    <h2 class="font-black">Vérifier la preuve</h2>
                    <p class="text-sm text-slate-500">Les kg déclarés correspondent-ils à la preuve jointe ?</p>
                    <label class="block text-sm font-bold">Note (obligatoire pour une correction)<textarea name="admin_note" rows="3" class="field">{{ old('admin_note') }}</textarea></label>
                    <div class="grid grid-cols-2 gap-2">
                        <button name="decision" value="correction" class="rounded-xl border border-rose-200 px-3 py-2.5 text-sm font-bold text-rose-700 hover:bg-rose-50">Demander une correction</button>
                        <x-module.button module="repair" name="decision" value="approve">Valider</x-module.button>
                    </div>
                </form>
            @endif

            <section class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 text-sm dark:border-amber-900 dark:bg-amber-950/20">
                <p class="font-black text-amber-900 dark:text-amber-200">Historique</p>
                <ul class="mt-3 space-y-2 text-slate-600 dark:text-slate-300">
                    <li>Déclaré le {{ $return->created_at->format('d/m/Y H:i') }}@if($return->requester) par {{ $return->requester->name }}@endif</li>
                    @if($return->accepted_at)<li>Reprise acceptée le {{ $return->accepted_at->format('d/m/Y H:i') }}</li>@endif
                    @if($return->shipped_at)<li>Renvoyé le {{ $return->shipped_at->format('d/m/Y H:i') }}</li>@endif
                    @if($return->received_at)<li>Reçu le {{ $return->received_at->format('d/m/Y H:i') }}</li>@endif
                    @if($return->completed_at)<li>Seconde vie déclarée le {{ $return->completed_at->format('d/m/Y H:i') }}</li>@endif
                    @if($return->verified_at)<li>Vérifié le {{ $return->verified_at->format('d/m/Y H:i') }}@if($return->verifier) par {{ $return->verifier->name }}@endif</li>@endif
                </ul>
            </section>
        </aside>
    </div>
</x-dynamic-component>
