@php
$layout = ['brand' => 'brand-layout', 'supplier' => 'module.supplier-layout', 'admin' => 'app-layout'][$space];
$showRoute = ['brand' => 'brand.supplier-returns.show', 'supplier' => 'supplier.returns.show', 'admin' => 'admin.supplier-returns.show'][$space];
$kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg';
$subtitle = [
    'brand' => 'Matière en trop, défectueuse ou livrée par erreur : renvoyez-la à votre fournisseur, il la remet dans le cycle.',
    'supplier' => 'Les marques vous renvoient de la matière. Reprenez-la et prouvez sa seconde vie : revente, recyclage ou surcyclage.',
    'admin' => 'Tous les retours entre marques et fournisseurs. Votre rôle : vérifier les preuves de seconde vie.',
][$space];
$columns = [
    'requested' => ['Demande', ['requested']],
    'accepted' => ['Reprise acceptée', ['accepted']],
    'in_transit' => ['En renvoi', ['in_transit']],
    'received' => ['Reçu', ['received']],
    'completed' => ['Seconde vie', ['completed', 'correction']],
    'verified' => ['Vérifié', ['verified']],
];
$mine = $returns->filter(fn ($return) => $return->nextActor() === $space)->count();
@endphp
<x-dynamic-component :component="$layout" title="Seconde vie de la matière">
    @include('modules.supplier-returns._header', [
        'eyebrow' => 'Seconde vie de la matière',
        'title' => 'Flux des retours fournisseurs',
        'subtitle' => $subtitle,
        'actionsHtml' => $space === 'brand' ? '<a href="'.route('brand.suppliers.index').'" class="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">+ Renvoyer un lot</a>' : '',
        'chips' => [
            [$mine, $mine > 1 ? 'retours attendent votre action' : 'retour attend votre action'],
            [$kg($stats['returned_kg']), 'renvoyés'],
            [$kg($stats['saved_kg']), 'sauvés du déchet'],
            [$stats['saved_rate'] === null ? '—' : $stats['saved_rate'].' %', 'taux de valorisation'],
        ],
    ])

    @if($space === 'admin')@include('modules.repair.admin._tabs')@endif
    @if($space === 'brand')@include('modules.suppliers.brand._tabs')@endif

    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <span class="font-semibold text-slate-500">Motif :</span>
        @foreach(['' => 'Tous'] + \App\Models\SupplierReturn::REASONS as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['reason' => $value ?: null]) }}"
               class="rounded-lg px-3 py-1.5 font-semibold {{ request('reason', '') === $value ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">{{ $label }}</a>
        @endforeach
        <span class="ml-auto inline-flex items-center gap-2 text-xs text-slate-500"><span class="h-2.5 w-2.5 rounded-full bg-lime-400 ring-4 ring-lime-100 dark:ring-lime-900/40"></span>à vous d’agir</span>
    </div>

    {{-- Tableau de flux (kanban) : chaque colonne est une étape du parcours de la matière. --}}
    <div class="-mx-4 overflow-x-auto px-4 pb-4">
        <div class="grid min-w-[1100px] grid-cols-6 gap-3">
            @foreach($columns as $key => [$label, $statuses])
                @php $items = $returns->whereIn('status', $statuses); @endphp
                <section class="flex flex-col rounded-2xl bg-slate-100/80 p-2.5 dark:bg-slate-800/50" aria-label="{{ $label }}">
                    <header class="flex items-center justify-between px-1.5 pb-2.5 pt-1">
                        <h2 class="text-[13px] font-black text-slate-700 dark:text-slate-200">{{ $loop->iteration }}. {{ $label }}</h2>
                        <span class="rounded-md bg-white px-1.5 text-xs font-bold tabular-nums text-slate-500 dark:bg-slate-900">{{ $items->count() }}</span>
                    </header>
                    <div class="space-y-2.5">
                        @forelse($items as $return)
                            @php $yours = $return->nextActor() === $space; @endphp
                            <a href="{{ route($showRoute, $return) }}"
                               class="block rounded-xl border bg-white p-3 text-sm shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-900 {{ $yours ? 'border-lime-400 ring-2 ring-lime-200 dark:ring-lime-900/50' : 'border-slate-200 dark:border-slate-700' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="mod-display text-lg font-extrabold leading-none tabular-nums text-teal-800 dark:text-teal-300">{{ $kg($return->quantity_kg) }}</span>
                                    <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ ['surplus' => 'bg-teal-50 text-teal-700', 'defect' => 'bg-rose-50 text-rose-700', 'wrong_item' => 'bg-indigo-50 text-indigo-700'][$return->reason] ?? 'bg-slate-100' }}">{{ ['surplus' => 'Surplus', 'defect' => 'Défaut', 'wrong_item' => 'Erreur'][$return->reason] ?? $return->reason }}</span>
                                </div>
                                <p class="mt-2 truncate font-bold text-slate-900 dark:text-white">{{ $return->material?->name ?? 'Matière' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $space === 'brand' ? $return->supplier?->name : ($space === 'supplier' ? $return->brand?->name : $return->brand?->name.' → '.$return->supplier?->name) }}</p>
                                @if($return->savedKg() > 0)
                                    <div class="mt-2 flex h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" title="Répartition de la seconde vie">
                                        @foreach([['bg-teal-700', $return->resold_kg], ['bg-lime-500', $return->recycled_kg], ['bg-teal-300', $return->upcycled_kg]] as [$color, $value])
                                            <span class="{{ $color }}" style="width: {{ (float) $return->quantity_kg > 0 ? (float) $value / (float) $return->quantity_kg * 100 : 0 }}%"></span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="mt-2.5 flex items-center justify-between gap-2 text-[11px]">
                                    @unless($yours)<span class="text-slate-400">{{ $return->reference }}</span>@endunless
                                    @if($return->status === 'correction')
                                        <span class="font-bold text-rose-600">Correction</span>
                                    @elseif($yours)
                                        <span class="font-bold text-lime-700 dark:text-lime-300">→ {{ $return->nextAction() }}</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-300 px-3 py-6 text-center text-xs text-slate-400 dark:border-slate-700">Vide</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
    @if($returns->isEmpty() && $space === 'brand')
        <p class="mt-2 text-sm text-slate-500">Pour commencer : ouvrez une commande reçue et cliquez sur « Renvoyer » à côté d’un lot.</p>
    @endif
</x-dynamic-component>
