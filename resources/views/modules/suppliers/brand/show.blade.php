<x-brand-layout>
    <x-module.hero module="suppliers" :eyebrow="'Fournisseur · '.$supplier->country" :title="$supplier->name" :description="($certifications = $supplier->certificationsLabel()) ? 'Certifications prouvées par ses certificats vérifiés : '.$certifications : 'Suivi des commandes, lots et certificats de provenance. Aucune certification vérifiée pour l’instant.'">
        <x-slot:actions>
            <x-module.button module="suppliers" variant="light" :href="route('brand.suppliers.edit', $supplier)">Modifier</x-module.button>
            @if($supplier->canBeDeleted())
                <form method="POST" action="{{ route('brand.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Supprimer ce fournisseur ? (aucune preuve de traçabilité ne sera perdue)')">@csrf @method('DELETE')<button class="rounded-xl border border-white/40 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur hover:bg-white/20">Supprimer</button></form>
            @else
                <form method="POST" action="{{ route('brand.suppliers.suspend', $supplier) }}" @unless($supplier->isSuspended()) onsubmit="return confirm('Suspendre ce fournisseur ? Plus de nouvelles commandes ; son historique et ses preuves sont conservés.')" @endunless>@csrf @method('PATCH')<button class="rounded-xl border border-white/40 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur hover:bg-white/20">{{ $supplier->isSuspended() ? 'Réactiver' : 'Suspendre' }}</button></form>
            @endif
        </x-slot:actions>
        <x-slot:stats>
            <x-module.stat label="Statut" :value="$supplier->statusLabel()"/>
            <x-module.stat label="Commandes" :value="$supplier->purchaseOrders->count()"/>
            <x-module.stat label="Lots" :value="$supplier->batches->count()" :hint="$supplier->batches->sum('quantity_kg').' kg au total'"/>
            <x-module.stat label="Conformité" :value="$supplier->compliance_score !== null ? $supplier->compliance_score.' %' : '—'"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.suppliers.brand._tabs')
    @if(session('warning'))
        <div role="status" class="mb-6 flex gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-amber-500 text-xs font-bold text-white">!</span><p>{{ session('warning') }}</p></div>
    @endif
    @if($supplier->isSuspended())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><strong>Fournisseur suspendu.</strong> Aucune nouvelle commande possible ; son historique, ses lots et ses certificats restent consultables comme preuves.</div>
    @endif
    @include('modules.suppliers.brand._access')
    @include('modules.suppliers._ai-risk', ['action' => route('brand.suppliers.ai-risk', $supplier)])

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Bons de commande : plusieurs lignes (matière, quantité, prix négocié), total calculé en direct --}}
        @php
            $initialLines = old('lines', [['material_id' => '', 'quantity_kg' => '', 'unit_price' => '']]);
            $fmtKg = fn (float $kg) => rtrim(rtrim(number_format($kg, 2, ',', ' '), '0'), ',');
        @endphp
        <section class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6 xl:col-span-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div><p class="text-[13px] font-semibold text-purple-600">Achats</p><h2 class="mt-1 text-xl font-black">Bons de commande</h2></div>
                <p class="text-sm text-slate-500">Le fournisseur accepte, refuse ou fait une contre-proposition, puis livre en une ou plusieurs fois.</p>
            </div>
            @unless($supplier->isSuspended())
            <form method="POST" action="{{ route('brand.purchase-orders.store', $supplier) }}" class="mt-5 rounded-2xl bg-purple-50/60 p-5"
                  x-data="{ lines: {{ json_encode(array_values($initialLines)) }}, total() { return this.lines.reduce((sum, l) => sum + (parseFloat(l.quantity_kg) || 0) * (parseFloat(l.unit_price) || 0), 0) }, kg() { return this.lines.reduce((sum, l) => sum + (parseFloat(l.quantity_kg) || 0), 0) } }">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-module.field name="ordered_at" type="date" label="Commandé le" :value="now()->toDateString()" required/>
                    <x-module.field name="expected_at" type="date" label="Livraison souhaitée"/>
                    <x-module.field name="notes" label="Message au fournisseur" placeholder="Ex. collection printemps, conditionnement en balles"/>
                </div>
                <div class="mt-5 space-y-2">
                    <div class="hidden grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_2.5rem] gap-3 text-[13px] font-semibold text-slate-600 sm:grid"><span>Matière</span><span>Quantité (kg)</span><span>Prix négocié (TND/kg)</span><span></span></div>
                    <template x-for="(line, index) in lines" :key="index">
                        <div class="grid gap-3 rounded-xl bg-white p-3 ring-1 ring-purple-100 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_2.5rem] sm:items-center sm:bg-transparent sm:p-0 sm:ring-0">
                            <select :name="`lines[${index}][material_id]`" x-model="line.material_id" required class="field mt-0" aria-label="Matière">
                                <option value="">Choisir une matière</option>
                                @foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach
                            </select>
                            <input type="number" step="0.1" min="0.1" required :name="`lines[${index}][quantity_kg]`" x-model="line.quantity_kg" class="field mt-0" placeholder="500" aria-label="Quantité en kg">
                            <input type="number" step="0.01" min="0" :name="`lines[${index}][unit_price]`" x-model="line.unit_price" class="field mt-0" placeholder="13,50" aria-label="Prix par kg">
                            <button type="button" @click="lines.splice(index, 1)" x-show="lines.length > 1" class="grid h-10 w-10 place-items-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label="Retirer la ligne">✕</button>
                        </div>
                    </template>
                    @if($errors->has('lines') || $errors->has('lines.*'))
                        <ul class="text-sm font-semibold text-rose-600">@foreach(collect($errors->get('lines'))->merge(collect($errors->get('lines.*'))->flatten())->unique() as $message)<li>{{ $message }}</li>@endforeach</ul>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" @click="lines.push({ material_id: '', quantity_kg: '', unit_price: '' })" x-show="lines.length < 10" class="rounded-lg px-3 py-2 text-sm font-semibold text-purple-800 ring-1 ring-inset ring-purple-200 hover:bg-white">+ Ajouter une matière</button>
                    <div class="flex items-center gap-5">
                        <p class="text-sm text-slate-600"><span x-text="kg().toLocaleString('fr-FR')"></span> kg · <strong class="text-slate-900" x-text="total().toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TND'"></strong></p>
                        <x-module.button module="suppliers">Envoyer la commande</x-module.button>
                    </div>
                </div>
            </form>
            @endunless

            <div class="mt-6 divide-y divide-slate-100">
                @forelse($supplier->purchaseOrders->sortByDesc('ordered_at') as $order)
                    <a href="{{ route('brand.purchase-orders.show', $order) }}" class="-mx-3 flex flex-wrap items-center justify-between gap-4 rounded-xl px-3 py-4 transition-colors hover:bg-purple-50/50">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-900">{{ $order->reference }}
                                @if($order->isLate())<span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">En retard</span>@endif
                                @if($order->awaitsBrandDecision())<span class="ml-2 rounded-full bg-purple-700 px-2 py-0.5 text-xs font-semibold text-white">Votre décision</span>@endif
                            </p>
                            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $order->lines->map(fn ($line) => $fmtKg($line->dueKg()).' kg '.($line->material?->name ?? ''))->join(' · ') ?: 'Sans ligne détaillée' }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            @if($order->lines->isNotEmpty() && ! in_array($order->status, ['declined', 'cancelled'], true))
                                <div class="hidden w-36 sm:block">
                                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $order->status === 'received' ? 'bg-emerald-500' : 'bg-purple-500' }}" style="width: {{ $order->shippedPercent() }}%"></div></div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $order->shippedPercent() }} % expédié</p>
                                </div>
                            @endif
                            <x-module.badge :tone="$order->statusTone()">{{ $order->statusLabel() }}</x-module.badge>
                            <span class="text-purple-600" aria-hidden="true">→</span>
                        </div>
                    </a>
                @empty
                    <p class="py-4 text-sm text-slate-500">Aucune commande pour ce fournisseur.</p>
                @endforelse
            </div>
        </section>

        {{-- Lots --}}
        <section class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6 xl:col-span-3">
            <p class="text-[13px] font-semibold text-purple-600">Provenance</p>
            <h2 class="mt-1 text-xl font-black">Lots de matières</h2>
            <p class="mt-1 text-sm text-slate-500">Un lot = une livraison de matière. Les lots sont créés uniquement par le fournisseur quand il expédie une commande, avec son certificat : la preuve vient de la source. Vous les consultez, confirmez la réception sur la commande et les reliez à vos produits (onglet Traçabilité produits).</p>
            @unless($supplier->managesOwnLots())
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    <p><strong>{{ $supplier->name }} n’a pas encore activé son accès.</strong> Il pourra déclarer ses livraisons et ses certificats dès qu’il l’aura activé.</p>
                    @unless($supplier->isSuspended())
                        <form method="POST" action="{{ route('brand.suppliers.access', $supplier) }}">@csrf<x-module.button module="suppliers" variant="soft">{{ $supplier->user ? 'Renvoyer l’invitation' : 'Inviter le fournisseur' }}</x-module.button></form>
                    @endunless
                </div>
            @endunless

            <div class="mt-5 space-y-4">
                @forelse($supplier->batches as $batch)
                    <article class="rounded-2xl border border-slate-200 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[13px] font-semibold text-purple-600">{{ $batch->material?->name ?? 'Matière non renseignée' }} · {{ $batch->origin_country }}</p>
                                <h3 class="mt-1 text-lg font-black">{{ $batch->batch_code }}</h3>
                                <p class="text-sm text-slate-500">{{ $batch->quantity_kg }} kg @if($batch->production_date) · produit le {{ $batch->production_date->format('d/m/Y') }}@endif @if($batch->purchaseOrder) · {{ $batch->purchaseOrder->reference }}@endif</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-module.badge :tone="['verified' => 'good', 'rejected' => 'bad'][$batch->status] ?? 'warn'">{{ $batch->statusLabel() }}</x-module.badge>
                            </div>
                        </div>
                        @if($batch->verification_note)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ $batch->verification_note }}</p>@endif
                        @if($batch->productLinks->isNotEmpty())<p class="mt-3 inline-flex rounded-xl bg-purple-50 px-3 py-2 text-xs font-bold text-purple-700">Utilisé dans : {{ $batch->productLinks->pluck('product.name')->filter()->join(', ') }}</p>@endif

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach($batch->certificates as $certificate)
                                @include('modules.suppliers._certificate', ['documentRoute' => 'brand.certificates.document'])
                            @endforeach

                            @if($batch->certificates->isEmpty())
                                <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-sm text-slate-500">Aucun certificat pour ce lot : {{ $supplier->name }} doit l’ajouter depuis son espace.</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-ui.empty message="Aucun lot déclaré pour ce fournisseur."/>
                @endforelse
            </div>
        </section>
    </div>
</x-brand-layout>
