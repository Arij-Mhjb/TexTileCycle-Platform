{{-- Espace fournisseur : toutes ses livraisons (lots) et leurs certificats ; il ajoute ou remplace un certificat lui-même. --}}
@php $fmt = fn (float $kg) => rtrim(rtrim(number_format($kg, 2, ',', ' '), '0'), ','); @endphp
<x-module.supplier-layout title="Mes lots & certificats">
    <x-module.hero module="suppliers" eyebrow="Espace fournisseur" title="Mes lots & certificats"
        description="Chaque livraison que vous expédiez devient un lot tracé. Joignez le certificat de provenance de chaque lot : c’est la preuve que vos clients montrent sur leurs produits.">
        <x-slot:stats>
            <x-module.stat label="Lots livrés" :value="$batches->count()"/>
            <x-module.stat label="Vérifiés" :value="$batches->where('status', 'verified')->count()"/>
            <x-module.stat label="Sans certificat valable" :value="$batches->filter(fn ($batch) => ! $batch->certificates->contains(fn ($c) => ! $c->isNotACertificate() && $c->status !== 'rejected'))->count()"/>
            <x-module.stat label="Kilos livrés" :value="$fmt((float) $batches->sum('quantity_kg'))"/>
        </x-slot:stats>
    </x-module.hero>

    <div class="space-y-4">
        @forelse($batches as $batch)
            @php $hasProof = $batch->certificates->contains(fn ($c) => ! $c->isNotACertificate() && $c->status !== 'rejected'); @endphp
            <article id="lot-{{ $batch->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[13px] font-semibold text-purple-700">{{ $batch->material?->name ?? 'Matière' }} · {{ $batch->origin_country }}@if($batch->purchaseOrder) · pour {{ $batch->purchaseOrder->brand->name }}@endif</p>
                        <h2 class="mt-1 text-lg font-black">{{ $batch->batch_code }}</h2>
                        <p class="text-sm text-slate-500">{{ $fmt((float) $batch->quantity_kg) }} kg · expédié le {{ $batch->created_at->format('d/m/Y') }}
                            @if($batch->purchaseOrder) · <a class="font-semibold text-purple-700 hover:underline" href="{{ route('supplier.orders.show', $batch->purchaseOrder) }}">{{ $batch->purchaseOrder->reference }}</a>@endif
                            @if($batch->received_at) · reçu par le client le {{ $batch->received_at->format('d/m/Y') }}@endif
                        </p>
                    </div>
                    <x-module.badge :tone="$batch->status === 'verified' ? 'good' : ($hasProof ? 'warn' : 'bad')">{{ $batch->status === 'verified' ? 'Vérifié par TexTileCycle' : ($hasProof ? 'Certificat en vérification' : 'Certificat manquant') }}</x-module.badge>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach($batch->certificates as $certificate)
                        @include('modules.suppliers._certificate', ['documentRoute' => 'supplier.certificates.document'])
                    @endforeach
                    @unless($batch->status === 'verified')
                        @include('modules.suppliers._add-certificate', ['action' => route('supplier.batches.certificate', $batch)])
                    @endunless
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-purple-200 bg-white p-10 text-center text-sm text-slate-500">Aucun lot pour l’instant : vos lots apparaissent dès que vous expédiez une commande.</div>
        @endforelse
    </div>
</x-module.supplier-layout>
