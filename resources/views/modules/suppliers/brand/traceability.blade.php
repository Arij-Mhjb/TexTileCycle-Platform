<x-brand-layout>
    <x-module.hero module="suppliers" title="Traçabilité des produits" description="Reliez chaque produit aux lots fournisseurs réellement utilisés. Le système compare automatiquement matière, origine et certificats avec la déclaration du produit.">
        <x-slot:stats>
            <x-module.stat label="Produits" :value="$products->count()"/>
            <x-module.stat label="Couverture" :value="$coverage.' %'" hint="produits reliés à au moins un lot"/>
            <x-module.stat label="Liens conformes" :value="$products->flatMap->supplierLinks->where('conformity_status', 'conform')->count()"/>
            <x-module.stat label="Écarts" :value="$products->flatMap->supplierLinks->where('conformity_status', 'mismatch')->count()"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.suppliers.brand._tabs')

    <form method="POST" action="{{ route('brand.traceability.store') }}" class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] mb-8 grid gap-4 rounded-3xl border border-purple-100 bg-white p-6 md:grid-cols-5">
        @csrf
        <div class="md:col-span-2"><x-ui.select name="product_id" label="Produit" :options="$products->pluck('name', 'id')" required/></div>
        <div class="md:col-span-2"><x-ui.select name="supplier_batch_id" label="Lot fournisseur" :options="$batches->mapWithKeys(fn ($batch) => [$batch->id => $batch->batch_code.' · '.($batch->material?->name ?? '?').' · '.$batch->supplier->name.' ('.$batch->statusLabel().')'])" required/></div>
        <x-module.field name="percentage" type="number" step="0.01" min="0" max="100" label="Part (%)"/>
        <div class="md:col-span-4"><x-module.field name="component" label="Composant" placeholder="Tissu principal, doublure…"/></div>
        <div class="flex items-end"><x-module.button module="suppliers" class="w-full">Associer et contrôler</x-module.button></div>
    </form>

    <div class="space-y-5">
        @forelse($products as $product)
            <article class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] rounded-3xl border border-slate-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wider text-slate-400">{{ $product->category }} · {{ $product->sku }}</p>
                        <h2 class="mt-1 text-xl font-black">{{ $product->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Composition déclarée :
                            @forelse($product->materials as $material){{ $material->name }}@if($material->pivot->percentage) {{ (float) $material->pivot->percentage }} %@endif @if($material->pivot->origin_country)({{ $material->pivot->origin_country }})@endif{{ $loop->last ? '' : ', ' }}@empty non renseignée @endforelse
                        </p>
                    </div>
                    <x-module.badge :tone="$product->supplierLinks->isEmpty() ? 'neutral' : ($product->supplierLinks->every(fn ($link) => $link->conformity_status === 'conform') ? 'good' : 'warn')">
                        {{ $product->supplierLinks->isEmpty() ? 'Aucun lot relié' : $product->supplierLinks->where('conformity_status', 'conform')->count().' / '.$product->supplierLinks->count().' conforme(s)' }}
                    </x-module.badge>
                </div>
                @if($product->supplierLinks->isNotEmpty())
                    <div class="mt-5 grid gap-3 lg:grid-cols-2">
                        @foreach($product->supplierLinks as $link)
                            <div class="rounded-2xl border p-4 {{ $link->conformity_status === 'conform' ? 'border-emerald-200 bg-emerald-50/50' : ($link->conformity_status === 'mismatch' ? 'border-amber-200 bg-amber-50/60' : 'border-slate-200') }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black">{{ $link->batch->batch_code }} <span class="font-semibold text-slate-500">· {{ $link->batch->material?->name }}</span></p>
                                        <p class="text-xs text-slate-500">{{ $link->batch->supplier->name }} · {{ $link->batch->origin_country }}@if($link->component) · {{ $link->component }}@endif @if($link->percentage) · {{ (float) $link->percentage }} %@endif</p>
                                    </div>
                                    <x-module.badge :tone="['conform' => 'good', 'mismatch' => 'warn'][$link->conformity_status] ?? 'neutral'">{{ $link->conformityLabel() }}</x-module.badge>
                                </div>
                                @if($link->conformity_note)<p class="mt-3 whitespace-pre-line text-xs leading-5 text-slate-600">{{ $link->conformity_note }}</p>@endif
                                <div class="mt-3 flex gap-2">
                                    <form method="POST" action="{{ route('brand.traceability.check', $link) }}">@csrf<button class="rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-bold text-white">Recontrôler</button></form>
                                    <form method="POST" action="{{ route('brand.traceability.destroy', $link) }}">@csrf @method('DELETE')<button class="rounded-lg px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Retirer</button></form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <x-ui.empty message="Aucun produit dans votre catalogue."/>
        @endforelse
    </div>
</x-brand-layout>
