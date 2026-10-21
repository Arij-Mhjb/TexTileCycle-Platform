@php
$layout = ['brand' => 'brand-layout', 'supplier' => 'module.supplier-layout', 'admin' => 'app-layout'][$space];
$kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg';
$subtitle = [
    'brand' => 'Pièces ratées, surplus, chutes : envoyez-les à vos fournisseurs. Ils les recyclent, puis vous récupérez la matière ou ils vous la rachètent. Le prix est calculé tout seul.',
    'supplier' => 'Les marques vous envoient leurs pièces ratées et leurs surplus. Vous les recyclez, puis vous rendez la matière ou vous la rachetez. Le prix est calculé tout seul.',
    'admin' => 'Toutes les demandes de recyclage entre marques et fournisseurs.',
][$space];
$actions = $space === 'brand'
    ? '<a href="'.route('brand.recycling.create').'" class="inline-flex items-center gap-2 rounded-2xl bg-teal-700 px-6 py-4 text-base font-black text-white shadow-sm hover:bg-teal-800"><span class="text-2xl leading-none">+</span> Recycler des articles</a>'
    : '';
@endphp
<x-dynamic-component :component="$layout" title="Recyclage">
    @include('modules.recycling._header', [
        'eyebrow' => 'Seconde vie de la matière',
        'title' => $space === 'supplier' ? 'Recyclage pour les marques' : 'Recyclage avec mes fournisseurs',
        'subtitle' => $subtitle,
        'actionsHtml' => $actions,
        'chips' => [
            [$stats['open'], 'demandes en cours'],
            [$kg($stats['sent_kg']), 'd’articles collectés'],
            [$kg($stats['recycled_kg']), 'de matière recyclée'],
            [$stats['yield'] === null ? '—' : $stats['yield'].' %', 'rendement moyen'],
        ],
    ])

    @if($space === 'admin')@include('modules.repair.admin._tabs')@endif
    @if($space === 'brand')@include('modules.suppliers.brand._tabs')@endif

    @if($todo->isEmpty() && $ongoing->isEmpty() && $done->isEmpty())
        <div class="rounded-3xl border border-dashed border-teal-200 bg-white p-12 text-center dark:border-teal-900 dark:bg-slate-900">
            <svg class="mx-auto h-14 w-14 text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M20 12a8 8 0 0 1-13.7 5.6M4 12a8 8 0 0 1 13.7-5.6"/><path d="M18 3v4h-4M6 21v-4h4"/></svg>
            <p class="mt-4 text-lg font-black">Aucune demande pour le moment</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                {{ $space === 'brand' ? 'Envoyez vos pièces défectueuses ou vos surplus à un fournisseur : il les transformera en nouvelle matière.' : 'Les demandes des marques apparaîtront ici.' }}
            </p>
            @if($space === 'brand')<div class="mt-6">{!! $actions !!}</div>@endif
        </div>
    @endif

    @if($todo->isNotEmpty())
        <section class="mb-8">
            <div class="mb-3 flex items-center gap-3">
                <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-60"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-teal-600"></span></span>
                <h2 class="text-lg font-black">À faire maintenant <span class="text-slate-400">· {{ $todo->count() }}</span></h2>
            </div>
            <div class="space-y-2.5">
                @foreach($todo as $cycle)@include('modules.recycling._row', ['cycle' => $cycle, 'space' => $space, 'highlight' => true])@endforeach
            </div>
        </section>
    @endif

    @if($ongoing->isNotEmpty())
        <section class="mb-8">
            <h2 class="mb-3 text-lg font-black">{{ $space === 'admin' ? 'En cours' : 'Chez l’autre partie' }} <span class="text-slate-400">· {{ $ongoing->count() }}</span></h2>
            <div class="space-y-2.5">
                @foreach($ongoing as $cycle)@include('modules.recycling._row', ['cycle' => $cycle, 'space' => $space, 'highlight' => false])@endforeach
            </div>
        </section>
    @endif

    @if($done->isNotEmpty())
        <section>
            <h2 class="mb-3 text-lg font-black">Terminées <span class="text-slate-400">· {{ $done->count() }}</span></h2>
            <div class="space-y-2.5 opacity-90">
                @foreach($done as $cycle)@include('modules.recycling._row', ['cycle' => $cycle, 'space' => $space, 'highlight' => false])@endforeach
            </div>
        </section>
    @endif
</x-dynamic-component>
