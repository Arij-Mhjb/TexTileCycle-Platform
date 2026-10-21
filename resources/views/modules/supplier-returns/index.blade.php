@php
$layout = ['brand' => 'brand-layout', 'supplier' => 'module.supplier-layout', 'admin' => 'app-layout'][$space];
$showRoute = ['brand' => 'brand.supplier-returns.show', 'supplier' => 'supplier.returns.show', 'admin' => 'admin.supplier-returns.show'][$space];
$kg = fn ($value) => number_format((float) $value, ($value == (int) $value) ? 0 : 1, ',', ' ').' kg';
$intro = [
    'brand' => 'Matière en trop, défectueuse ou livrée par erreur ? Renvoyez-la à votre fournisseur : il la reprend et lui donne une seconde vie au lieu de la jeter.',
    'supplier' => 'Les marques vous renvoient de la matière (surplus, défaut, erreur). Reprenez-la, puis déclarez sa seconde vie : revente, recyclage ou surcyclage.',
    'admin' => 'Suivi des retours de matière entre marques et fournisseurs. Vérifiez les preuves de seconde vie déclarées par les fournisseurs.',
][$space];
@endphp
<x-dynamic-component :component="$layout" title="Seconde vie de la matière">
    <x-module.hero module="repair" eyebrow="Seconde vie de la matière" title="Retours fournisseurs" :description="$intro">
        @if($space === 'brand')
            <x-slot:actions><x-module.button module="repair" variant="light" :href="route('brand.suppliers.index')">Choisir un lot à renvoyer</x-module.button></x-slot:actions>
        @endif
        <x-slot:stats>
            <x-module.stat label="Retours" :value="$stats['count']" :hint="$stats['open'].' en cours'"/>
            <x-module.stat label="Matière renvoyée" :value="$kg($stats['returned_kg'])"/>
            <x-module.stat label="Sauvée du déchet" :value="$kg($stats['saved_kg'])"/>
            <x-module.stat label="Taux de valorisation" :value="$stats['saved_rate'] === null ? '—' : $stats['saved_rate'].' %'"/>
        </x-slot:stats>
    </x-module.hero>

    @if($space === 'admin')@include('modules.repair.admin._tabs')@endif
    @if($space === 'brand')@include('modules.suppliers.brand._tabs')@endif

    <form class="mb-5 flex flex-wrap gap-2 text-sm">
        @foreach(['' => 'Tous'] + \App\Models\SupplierReturn::STATUSES as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $value ?: null, 'page' => null]) }}"
               class="rounded-full px-3 py-1.5 font-semibold {{ request('status', '') === $value ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-amber-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700' }}">{{ $label }}</a>
        @endforeach
    </form>

    <div class="space-y-4">
        @forelse($returns as $return)
            <a href="{{ route($showRoute, $return) }}" class="shadow-[0_1px_2px_rgba(15,23,42,.05)] block rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[13px] font-semibold text-amber-700">{{ $return->reference }} · {{ $return->reasonLabel() }}</p>
                        <h2 class="mt-1 text-lg font-black">{{ $kg($return->quantity_kg) }} de {{ $return->material?->name ?? 'matière' }}</h2>
                        <p class="text-sm text-slate-500">
                            @if($space !== 'brand'){{ $return->brand?->name }} → @endif{{ $space === 'supplier' ? 'vous' : $return->supplier?->name }}
                            · déclaré le {{ $return->created_at->format('d/m/Y') }}@if($return->destination) · {{ $return->destinationLabel() }}@endif
                        </p>
                    </div>
                    <div class="text-right">
                        <x-module.badge :tone="$return->statusTone()">{{ $return->statusLabel() }}</x-module.badge>
                        @if($return->savedKg() > 0)<p class="mt-2 text-sm font-black text-emerald-700">{{ $kg($return->savedKg()) }} sauvés</p>@endif
                    </div>
                </div>
                <div class="mt-4">@include('modules.supplier-returns._timeline', ['return' => $return])</div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">
                @if($space === 'brand')
                    Aucun retour pour le moment. Ouvrez une commande reçue et cliquez sur « Renvoyer » à côté d’un lot.
                @else
                    Aucun retour pour le moment.
                @endif
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $returns->links() }}</div>
</x-dynamic-component>
