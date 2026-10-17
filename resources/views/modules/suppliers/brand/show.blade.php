<x-brand-layout>
    <x-module.hero module="suppliers" :eyebrow="'Fournisseur · '.$supplier->country" :title="$supplier->name" :description="$supplier->certifications ? 'Certifications déclarées : '.$supplier->certifications : 'Suivi des commandes, lots et certificats de provenance.'">
        <x-slot:actions>
            <x-module.button module="suppliers" variant="light" :href="route('brand.suppliers.edit', $supplier)">Modifier</x-module.button>
            <form method="POST" action="{{ route('brand.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Supprimer ce fournisseur, ses lots et certificats ?')">@csrf @method('DELETE')<button class="rounded-xl border border-white/40 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur hover:bg-white/20">Supprimer</button></form>
        </x-slot:actions>
        <x-slot:stats>
            <x-module.stat label="Statut" :value="$supplier->statusLabel()"/>
            <x-module.stat label="Commandes" :value="$supplier->purchaseOrders->count()"/>
            <x-module.stat label="Lots" :value="$supplier->batches->count()" :hint="$supplier->batches->sum('quantity_kg').' kg au total'"/>
            <x-module.stat label="Conformité" :value="$supplier->compliance_score !== null ? $supplier->compliance_score.' %' : '—'"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.suppliers.brand._tabs')
    @include('modules.suppliers._ai-risk', ['action' => route('brand.suppliers.ai-risk', $supplier)])

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Bons de commande --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-black uppercase tracking-wider text-indigo-600">Achats</p>
            <h2 class="mt-1 text-xl font-black">Bons de commande</h2>
            <form method="POST" action="{{ route('brand.purchase-orders.store', $supplier) }}" class="mt-4 grid gap-3 rounded-2xl bg-indigo-50/60 p-4">
                @csrf
                <div class="grid grid-cols-2 gap-3"><x-module.field name="ordered_at" type="date" label="Commandé le" :value="now()->toDateString()" required/><x-module.field name="expected_at" type="date" label="Livraison prévue"/></div>
                <x-module.field name="total_amount" type="number" step="0.01" min="0" label="Montant (TND)"/>
                <x-module.button module="suppliers">Créer la commande</x-module.button>
            </form>
            <div class="mt-4 space-y-3">
                @forelse($supplier->purchaseOrders as $order)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex items-center justify-between gap-2"><p class="font-black">{{ $order->reference }}</p><x-module.badge :tone="['received' => 'good', 'cancelled' => 'bad'][$order->status] ?? 'suppliers'">{{ $order->statusLabel() }}</x-module.badge></div>
                        <p class="mt-1 text-xs text-slate-500">Commandée le {{ $order->ordered_at->format('d/m/Y') }}@if($order->expected_at) · prévue le {{ $order->expected_at->format('d/m/Y') }}@endif @if($order->total_amount) · {{ number_format((float) $order->total_amount, 2, ',', ' ') }} {{ $order->currency }}@endif</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $order->batches->count() }} lot(s) rattaché(s)</p>
                        @if($order->status === 'ordered')
                            <div class="mt-3 flex gap-2">
                                <form method="POST" action="{{ route('brand.purchase-orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="received"><button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white">Marquer reçue</button></form>
                                <form method="POST" action="{{ route('brand.purchase-orders.update', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="rounded-lg px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Annuler</button></form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aucune commande.</p>
                @endforelse
            </div>
        </section>

        {{-- Lots --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
            <p class="text-xs font-black uppercase tracking-wider text-indigo-600">Provenance</p>
            <h2 class="mt-1 text-xl font-black">Lots de matières</h2>
            <form method="POST" action="{{ route('brand.supplier-batches.store', $supplier) }}" class="mt-4 grid gap-3 rounded-2xl bg-indigo-50/60 p-4 md:grid-cols-3">
                @csrf
                <x-module.field name="batch_code" label="Code du lot" required placeholder="LOT-2026-001"/>
                <x-ui.select name="material_id" label="Matière" :options="$materials->pluck('name', 'id')" required/>
                <x-module.field name="quantity_kg" type="number" step="0.1" min="0.1" label="Quantité (kg)" required/>
                <x-module.field name="origin_country" label="Pays d’origine" :value="$supplier->country" required/>
                <x-module.field name="production_date" type="date" label="Production"/>
                <x-module.field name="received_at" type="date" label="Réception"/>
                <div class="md:col-span-2"><x-ui.select name="purchase_order_id" label="Bon de commande" :options="$supplier->purchaseOrders->where('status', '!=', 'cancelled')->pluck('reference', 'id')" placeholder="Aucun"/></div>
                <div class="flex items-end"><x-module.button module="suppliers" class="w-full">Déclarer le lot</x-module.button></div>
            </form>

            <div class="mt-5 space-y-4">
                @forelse($supplier->batches as $batch)
                    <article class="rounded-2xl border border-slate-200 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wider text-indigo-600">{{ $batch->material?->name ?? 'Matière non renseignée' }} · {{ $batch->origin_country }}</p>
                                <h3 class="mt-1 text-lg font-black">{{ $batch->batch_code }}</h3>
                                <p class="text-sm text-slate-500">{{ $batch->quantity_kg }} kg @if($batch->production_date) · produit le {{ $batch->production_date->format('d/m/Y') }}@endif @if($batch->purchaseOrder) · {{ $batch->purchaseOrder->reference }}@endif</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-module.badge :tone="['verified' => 'good', 'rejected' => 'bad'][$batch->status] ?? 'warn'">{{ $batch->statusLabel() }}</x-module.badge>
                                @if($batch->status !== 'verified')<form method="POST" action="{{ route('brand.supplier-batches.destroy', $batch) }}" onsubmit="return confirm('Supprimer ce lot ?')">@csrf @method('DELETE')<button class="btn-danger !py-1 text-xs">Supprimer</button></form>@endif
                            </div>
                        </div>
                        @if($batch->verification_note)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ $batch->verification_note }}</p>@endif
                        @if($batch->productLinks->isNotEmpty())<p class="mt-3 inline-flex rounded-xl bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">Utilisé dans : {{ $batch->productLinks->pluck('product.name')->filter()->join(', ') }}</p>@endif

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach($batch->certificates as $certificate)
                                @include('modules.suppliers._certificate', ['documentRoute' => 'brand.certificates.document'])
                            @endforeach

                            {{-- Ajout d'un certificat : une simple photo, l'IA lit le reste --}}
                            <form method="POST" action="{{ route('brand.certificates.store', $batch) }}" enctype="multipart/form-data" x-data="{ name: '' }"
                                  class="flex flex-col justify-center gap-3 rounded-2xl border-2 border-dashed border-violet-300 bg-gradient-to-br from-fuchsia-50/60 to-violet-50/60 p-4 text-center">
                                @csrf
                                <p class="text-sm font-black text-violet-800">✦ Ajouter un certificat</p>
                                <p class="text-xs text-slate-500">Envoyez la photo ou le scan : l’IA lit le type, le numéro, l’organisme et les dates.</p>
                                <label class="mx-auto cursor-pointer rounded-xl bg-white px-4 py-2 text-xs font-bold text-violet-700 shadow-sm ring-1 ring-violet-200 hover:bg-violet-50">
                                    <span x-text="name || 'Choisir la photo du certificat'"></span>
                                    <input type="file" name="document" accept="image/jpeg,image/png,image/webp" required class="sr-only" @change="name = $event.target.files[0]?.name || ''">
                                </label>
                                <button class="mx-auto rounded-xl bg-gradient-to-r from-fuchsia-600 to-violet-600 px-4 py-2 text-sm font-black text-white shadow-sm" :disabled="!name" :class="!name && 'opacity-50 cursor-not-allowed'">Envoyer à l’IA</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <x-ui.empty message="Aucun lot déclaré pour ce fournisseur."/>
                @endforelse
            </div>
        </section>
    </div>
</x-brand-layout>
