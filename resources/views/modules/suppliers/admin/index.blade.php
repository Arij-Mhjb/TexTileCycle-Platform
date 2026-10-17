<x-app-layout title="Fournisseurs & traçabilité">
    <x-module.hero module="suppliers" title="Fournisseurs & traçabilité des lots" description="Vérifiez les lots déclarés par les marques et leurs certificats de provenance avant qu’ils ne servent de preuve aux labels.">
        <x-slot:stats>
            <x-module.stat label="Fournisseurs" :value="$stats['suppliers']"/>
            <x-module.stat label="Lots déclarés" :value="$stats['batches']"/>
            <x-module.stat label="À vérifier" :value="$stats['pending']" hint="lots + certificats"/>
            <x-module.stat label="Conformité moyenne" :value="$stats['score'].' %'"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.suppliers.admin._tabs')

    <div class="mb-8 grid gap-6 xl:grid-cols-2">
        <section class="panel p-6">
            <p class="text-xs font-black uppercase tracking-wider text-indigo-600">File de vérification</p>
            <h2 class="mt-1 text-lg font-black">Lots en attente</h2>
            <div class="mt-4 space-y-3">
                @forelse($pendingBatches as $batch)
                    <div class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-black">{{ $batch->batch_code }} <span class="text-sm font-semibold text-slate-500">· {{ $batch->material?->name }} · {{ $batch->quantity_kg }} kg</span></p>
                            <x-module.badge tone="warn">{{ $batch->statusLabel() }}</x-module.badge>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $batch->supplier->brand->name }} → <a class="font-bold text-indigo-600" href="{{ route('admin.suppliers.show', $batch->supplier) }}">{{ $batch->supplier->name }}</a> · origine {{ $batch->origin_country }} · {{ $batch->certificates->count() }} certificat(s)</p>
                        @include('modules.suppliers.admin._review', ['action' => route('admin.supplier-batches.review', $batch)])
                    </div>
                @empty
                    <x-ui.empty message="Aucun lot en attente."/>
                @endforelse
            </div>
        </section>
        <section class="panel p-6">
            <p class="text-xs font-black uppercase tracking-wider text-indigo-600">Preuves de provenance</p>
            <h2 class="mt-1 text-lg font-black">Certificats à contrôler</h2>
            <div class="mt-4 space-y-3">
                @forelse($pendingCertificates as $certificate)
                    <div class="rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-black">{{ $certificate->type }} · n° {{ $certificate->certificate_number }}</p>
                            @if($certificate->isExpired())<x-module.badge tone="bad">Expiré</x-module.badge>@else<x-module.badge tone="warn">À vérifier</x-module.badge>@endif
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $certificate->issuer }} · émis le {{ $certificate->issued_at->format('d/m/Y') }}@if($certificate->expires_at) · expire le {{ $certificate->expires_at->format('d/m/Y') }}@endif · lot {{ $certificate->batch->batch_code }} ({{ $certificate->batch->supplier->brand->name }})</p>
                        @if($certificate->document_url)<a href="{{ $certificate->document_url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-bold text-indigo-600 underline">Ouvrir le document</a>@endif
                        @include('modules.suppliers.admin._review', ['action' => route('admin.certificates.review', $certificate)])
                    </div>
                @empty
                    <x-ui.empty message="Aucun certificat en attente."/>
                @endforelse
            </div>
        </section>
    </div>

    <div class="panel overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
            <form class="flex flex-1 flex-wrap gap-3">
                <input name="search" value="{{ request('search') }}" placeholder="Fournisseur ou pays…" class="field !mt-0 max-w-xs">
                <select name="status" class="field !mt-0 max-w-[12rem]" onchange="this.form.submit()"><option value="">Tous les statuts</option>@foreach(['active' => 'Actif', 'under_review' => 'En revue', 'suspended' => 'Suspendu'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-50"><tr>@foreach(['Fournisseur', 'Marque', 'Pays', 'Lots', 'À vérifier', 'Conformité', ''] as $heading)<th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td class="px-5 py-4 text-sm"><p class="font-bold">{{ $supplier->name }}</p><p class="text-xs text-slate-400">{{ $supplier->statusLabel() }}</p></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $supplier->brand->name }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $supplier->country }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $supplier->batches_count }}</td>
                            <td class="px-5 py-4 text-sm">@if($supplier->pending_batches_count)<x-module.badge tone="warn">{{ $supplier->pending_batches_count }}</x-module.badge>@else — @endif</td>
                            <td class="w-48 px-5 py-4"><x-module.bar module="suppliers" :value="$supplier->compliance_score ?? 0" :display="$supplier->compliance_score !== null ? $supplier->compliance_score.' %' : '—'" label="Score"/></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.suppliers.show', $supplier) }}" class="rounded-lg px-3 py-2 text-sm font-bold text-indigo-600 hover:bg-indigo-50">Détails</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty message="Aucun fournisseur déclaré par les marques."/></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())<div class="border-t border-slate-100 p-4">{{ $suppliers->links() }}</div>@endif
    </div>
</x-app-layout>
