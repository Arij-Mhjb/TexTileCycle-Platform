@php
$kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg';
$money = fn ($value) => number_format((float) $value, 0, ',', ' ').' TND';
$card = 'rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900';
$filters = ['all' => 'Toutes', 'open' => 'En cours', 'closed' => 'Terminées', 'declined' => 'Refusées'];
$columnTitles = ['Marques', 'Fournisseurs', 'Matière obtenue'];
@endphp
<x-app-layout title="Recyclage fournisseurs">
    @include('modules.recycling._header', [
        'eyebrow' => 'Seconde vie de la matière · vue admin',
        'title' => 'Flux de recyclage',
        'subtitle' => $stats['brands'].' marque(s) et '.$stats['suppliers'].' fournisseur(s) échangent leurs rebuts. Voici ce qui circule, ce que ça devient, et où ça bloque.',
        'chips' => [
            [$stats['open'].' / '.$stats['loops'], 'demandes en cours'],
            [$kg($stats['sent_kg']), 'de rebuts collectés'],
            [$kg($stats['recycled_kg']), 'de matière recyclée'],
            [$stats['yield'] === null ? '—' : $stats['yield'].' %', 'rendement moyen'],
        ],
    ])

    @include('modules.repair.admin._tabs')

    {{-- Sankey : Marques → Fournisseurs → Matière recyclée --}}
    <section class="{{ $card }}" x-data="{ focus: null }">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-black">Flux de matière</h2>
                <p class="text-sm text-slate-500">Épaisseur = kilos. Survolez un flux pour le détail. Les demandes refusées sont exclues.</p>
            </div>
            <p class="flex items-center gap-4 text-xs font-semibold text-slate-500">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-5 rounded-sm bg-teal-600/60"></span>Matière recyclée</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-5 rounded-sm bg-slate-300 dark:bg-slate-600"></span>Pertes</span>
            </p>
        </div>

        @if($sankey['nodes'] === [])
            <p class="mt-6 rounded-2xl bg-slate-50 p-8 text-center text-sm text-slate-500 dark:bg-slate-800/60">Aucun flux pour le moment : les demandes acceptées apparaîtront ici.</p>
        @else
            <div class="mt-6 overflow-x-auto">
                <svg viewBox="0 -28 {{ \App\Services\RecyclingFlowService::WIDTH }} {{ $sankey['height'] + 40 }}" class="min-w-[720px] w-full" role="img" aria-label="Flux de matière entre marques, fournisseurs et matières recyclées, {{ $kg($sankey['total']) }} au total">
                    @foreach([150, 490, 830] as $i => $x)
                        <text x="{{ $i === 0 ? $x + 14 : $x }}" y="-12" text-anchor="{{ $i === 0 ? 'end' : 'start' }}" class="fill-slate-400 text-[12px] font-bold uppercase tracking-wider">{{ $columnTitles[$i] }}</text>
                    @endforeach
                    <g fill="none">
                        @foreach($sankey['links'] as $index => $link)
                            <path d="{{ $link['path'] }}" stroke-width="{{ $link['width'] }}"
                                  class="cursor-pointer transition-opacity {{ $link['loss'] ? 'stroke-slate-300 dark:stroke-slate-600' : 'stroke-teal-600 dark:stroke-teal-400' }}"
                                  :style="{ opacity: focus === null ? {{ $link['loss'] ? '.55' : '.38' }} : (focus === {{ $index }} ? .85 : .12) }"
                                  @mouseenter="focus = {{ $index }}" @mouseleave="focus = null">
                                <title>{{ $link['label'] }} : {{ $kg($link['value']) }}</title>
                            </path>
                        @endforeach
                    </g>
                    @foreach($sankey['nodes'] as $node)
                        <rect x="{{ $node['x'] }}" y="{{ $node['y'] }}" width="14" height="{{ $node['h'] }}" rx="3" class="{{ $node['id'] === 'loss' ? 'fill-slate-400' : 'fill-teal-700 dark:fill-teal-300' }}"><title>{{ $node['label'] }} : {{ $kg($node['value']) }}</title></rect>
                        @php $left = $node['column'] === 0; $cy = $node['y'] + $node['h'] / 2; @endphp
                        <text x="{{ $left ? $node['x'] - 10 : $node['x'] + 24 }}" y="{{ $cy - 2 }}" text-anchor="{{ $left ? 'end' : 'start' }}" paint-order="stroke" stroke-width="4" stroke-linejoin="round" class="fill-slate-800 stroke-white text-[13px] font-bold dark:fill-slate-100 dark:stroke-slate-900">{{ \Illuminate\Support\Str::limit($node['label'], 26) }}</text>
                        <text x="{{ $left ? $node['x'] - 10 : $node['x'] + 24 }}" y="{{ $cy + 14 }}" text-anchor="{{ $left ? 'end' : 'start' }}" paint-order="stroke" stroke-width="4" stroke-linejoin="round" class="fill-slate-500 stroke-white text-[12px] tabular-nums dark:fill-slate-400 dark:stroke-slate-900">{{ $kg($node['value']) }}</text>
                    @endforeach
                </svg>
            </div>
            <p class="mt-2 text-xs text-slate-500" x-show="focus !== null" x-cloak>
                @foreach($sankey['links'] as $index => $link)<span x-show="focus === {{ $index }}"><strong class="text-slate-700 dark:text-slate-200">{{ $link['label'] }}</strong> · {{ $kg($link['value']) }}</span>@endforeach
            </p>
        @endif
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Entonnoir --}}
        <section class="{{ $card }}">
            <h2 class="text-lg font-black">Où en sont les demandes</h2>
            <p class="text-sm text-slate-500">Nombre de demandes arrivées à chaque étape.</p>
            <ol class="mt-5 space-y-3">
                @foreach($funnel as $step)
                    <li class="group" title="{{ $step['label'] }} : {{ $step['count'] }} demande(s), {{ $step['percent'] }} %">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $step['label'] }}</span>
                            <span class="tabular-nums text-slate-500"><strong class="text-slate-900 dark:text-white">{{ $step['count'] }}</strong> · {{ $step['percent'] }} %</span>
                        </div>
                        <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-2.5 rounded-full bg-teal-600 transition-all group-hover:bg-teal-700 dark:bg-teal-400" style="width: {{ max(2, $step['percent']) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ol>
            <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60"><dt class="text-slate-500">Frais de recyclage payés</dt><dd class="mt-1 text-xl font-black tabular-nums">{{ $money($stats['fees']) }}</dd></div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60"><dt class="text-slate-500">Avoirs versés aux marques</dt><dd class="mt-1 text-xl font-black tabular-nums">{{ $money($stats['credits']) }}</dd></div>
            </dl>
        </section>

        {{-- Demandes qui bloquent --}}
        <section class="{{ $card }}">
            <h2 class="text-lg font-black">Demandes qui n’avancent pas</h2>
            <p class="text-sm text-slate-500">En cours, sans action depuis plus de 5 jours.</p>
            <div class="mt-5 space-y-2.5">
                @forelse($stalled as $cycle)
                    <a href="{{ route('admin.recycling.show', $cycle) }}" class="flex items-center gap-4 rounded-2xl border border-slate-200 p-4 transition hover:border-teal-300 dark:border-slate-700">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-50 text-center text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900">
                            <span class="leading-none"><span class="block text-base font-black">{{ (int) $cycle->updated_at->diffInDays(now()) }}</span><span class="text-[10px] font-bold">jours</span></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-black">{{ $cycle->brand->name }} → {{ $cycle->supplier->name }}</span>
                            <span class="block truncate text-sm text-slate-500">{{ $cycle->reference }} · au tour de {{ $cycle->nextActor() === 'brand' ? $cycle->brand->name : $cycle->supplier->name }} : {{ mb_strtolower($cycle->nextAction()) }}</span>
                        </span>
                    </a>
                @empty
                    <div class="flex items-center gap-3 rounded-2xl bg-teal-50 p-4 text-sm font-semibold text-teal-800 dark:bg-teal-950/40 dark:text-teal-200">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                        Rien de bloqué : toutes les demandes avancent.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Toutes les demandes --}}
    <section class="mt-8" x-data="{ filter: 'all', search: '' }">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-black">Toutes les demandes <span class="text-slate-400">· {{ $loops->count() }}</span></h2>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800" role="group" aria-label="Filtrer">
                    @foreach($filters as $value => $label)
                        <button type="button" @click="filter = '{{ $value }}'" class="rounded-lg px-3 py-1.5 text-sm font-bold transition" :class="filter === '{{ $value }}' ? 'bg-white text-teal-800 shadow-sm dark:bg-slate-900 dark:text-teal-200' : 'text-slate-500'">{{ $label }}</button>
                    @endforeach
                </div>
                <input type="search" x-model="search" placeholder="Marque, fournisseur, référence…" class="field !mt-0 w-64">
            </div>
        </div>
        <div class="space-y-2.5">
            @forelse($loops as $cycle)
                @php $group = $cycle->isOpen() ? 'open' : $cycle->status; @endphp
                <div x-show="(filter === 'all' || filter === '{{ $group }}') && @js(mb_strtolower($cycle->reference.' '.$cycle->brand?->name.' '.$cycle->supplier?->name)).includes(search.toLowerCase())">
                    @include('modules.recycling._row', ['cycle' => $cycle, 'space' => 'admin', 'highlight' => false])
                </div>
            @empty
                <p class="rounded-3xl border border-dashed border-teal-200 bg-white p-10 text-center text-sm text-slate-500 dark:border-teal-900 dark:bg-slate-900">Aucune demande pour le moment.</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
