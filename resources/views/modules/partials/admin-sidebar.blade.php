{{-- Menu admin des modules Fournisseurs (violet-rose), Impact ESG (sky) et Seconde vie (amber). --}}
@php
$moduleGroups = [
    ['label' => 'Fournisseurs & traçabilité', 'dot' => 'bg-fuchsia-400', 'items' => [
        ['admin.suppliers.index', 'admin.suppliers.*', 'Fournisseurs & lots', 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01'],
        ['admin.traceability.index', 'admin.traceability.*', 'Conformité des lots', 'M9 12l2 2 4-4M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z'],
    ]],
    ['label' => 'Impact ESG', 'dot' => 'bg-sky-400', 'items' => [
        ['admin.impact-reports.index', 'admin.impact-reports.*', 'Rapports d’impact', 'M4 20V10m6 10V4m6 16v-7m4 7H2'],
        ['admin.impact-categories.index', 'admin.impact-categories.*', 'Catégories d’impact', 'M12 3c3 3 5 5.5 5 9a5 5 0 1 1-10 0c0-3.5 2-6 5-9Z'],
        ['admin.impact-metrics.index', 'admin.impact-metrics.*', 'Facteurs matières', 'M4 6h16M4 12h16M4 18h10'],
    ]],
    ['label' => 'Seconde vie', 'dot' => 'bg-amber-400', 'items' => [
        ['admin.repair-orders.index', 'admin.repair-orders.*', 'Réparations', 'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6Z'],
        ['admin.marketplace.index', 'admin.marketplace.*', 'Marketplace', 'M3 9l1.5-5h15L21 9M3 9h18M3 9v11h18V9M9 20v-6h6v6'],
        ['admin.trade-ins.index', 'admin.trade-ins.*', 'Reprises', 'M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4'],
        ['admin.repair-centers.index', 'admin.repair-centers.*', 'Centres de réparation', 'M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z'],
    ]],
];
@endphp
@foreach($moduleGroups as $group)
    <div>
        <p class="mb-2 flex items-center gap-2 px-3 text-[10px] font-bold uppercase tracking-wider text-indigo-300"><span class="h-2 w-2 rounded-full {{ $group['dot'] }}"></span>{{ $group['label'] }}</p>
        <div class="space-y-1">
            @foreach($group['items'] as [$route, $pattern, $label, $icon])
                <a href="{{ route($route) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($pattern) ? 'bg-white/15 text-white shadow-sm' : 'text-indigo-100 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-5 w-5 {{ request()->routeIs($pattern) ? 'text-white' : 'text-indigo-300 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>{{ $label }}
                </a>
            @endforeach
        </div>
    </div>
@endforeach
