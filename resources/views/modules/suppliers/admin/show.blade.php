<x-app-layout :title="$supplier->name">
    <x-module.hero module="suppliers" :eyebrow="'Fournisseur de '.$supplier->brand->name" :title="$supplier->name" :description="collect([$supplier->address, $supplier->city, $supplier->country])->filter()->join(', ')">
        <x-slot:actions><x-module.button module="suppliers" variant="light" :href="route('admin.suppliers.index')">← Retour</x-module.button></x-slot:actions>
        <x-slot:stats>
            <x-module.stat label="Statut" :value="$supplier->statusLabel()"/>
            <x-module.stat label="Commandes" :value="$supplier->purchaseOrders->count()"/>
            <x-module.stat label="Lots" :value="$supplier->batches->count()"/>
            <x-module.stat label="Conformité" :value="$supplier->compliance_score !== null ? $supplier->compliance_score.' %' : '—'"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.suppliers.admin._tabs')
    @include('modules.suppliers._ai-risk', ['action' => route('admin.suppliers.ai-risk', $supplier)])

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel p-6">
            <h2 class="text-lg font-black">Contact</h2>
            <dl class="mt-4 space-y-3 text-sm">
                @foreach(['Contact' => $supplier->contact_name, 'E-mail' => $supplier->email, 'Téléphone' => $supplier->phone, 'Certifications' => $supplier->certifications, 'Notes' => $supplier->notes] as $label => $value)
                    <div><dt class="text-[13px] font-bold text-slate-400">{{ $label }}</dt><dd class="mt-0.5 text-slate-700">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="space-y-4 lg:col-span-2">
            @forelse($supplier->batches as $batch)
                <article class="panel p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[13px] font-semibold text-purple-600">{{ $batch->material?->name }} · {{ $batch->origin_country }}</p>
                            <h3 class="mt-1 text-lg font-black">{{ $batch->batch_code }}</h3>
                            <p class="text-sm text-slate-500">{{ $batch->quantity_kg }} kg · utilisé dans {{ $batch->productLinks->count() }} produit(s)</p>
                        </div>
                        <x-module.badge :tone="['verified' => 'good', 'rejected' => 'bad'][$batch->status] ?? 'warn'">{{ $batch->statusLabel() }}</x-module.badge>
                    </div>
                    @if($batch->verifier)<p class="mt-2 text-xs text-slate-500">Revu par {{ $batch->verifier->name }} le {{ $batch->verified_at?->format('d/m/Y') }} @if($batch->verification_note)— « {{ $batch->verification_note }} »@endif</p>@endif
                    @include('modules.suppliers.admin._review', ['action' => route('admin.supplier-batches.review', $batch)])
                    <div class="mt-4 space-y-2">
                        @foreach($batch->certificates as $certificate)
                            @include('modules.suppliers._certificate', ['documentRoute' => 'admin.certificates.document', 'review' => route('admin.certificates.review', $certificate)])
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="panel"><x-ui.empty message="Aucun lot déclaré."/></div>
            @endforelse
        </section>
    </div>
</x-app-layout>
