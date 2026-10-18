{{-- Menu admin des modules Fournisseurs (violet-rose), Impact ESG (sky) et Seconde vie (amber). --}}
@php
$moduleGroups = [
    ['label' => 'Fournisseurs & traçabilité', 'dot' => 'bg-purple-500', 'active' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300', 'icon_active' => 'text-purple-600 dark:text-purple-400', 'items' => [
        ['admin.suppliers.index', 'admin.suppliers.*', 'Fournisseurs & lots', 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01'],
        ['admin.traceability.index', 'admin.traceability.*', 'Conformité des lots', 'M9 12l2 2 4-4M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z'],
    ]],
    ['label' => 'Impact ESG', 'dot' => 'bg-sky-500', 'active' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300', 'icon_active' => 'text-sky-600 dark:text-sky-400', 'items' => [
        ['admin.impact-reports.index', 'admin.impact-reports.*', 'Rapports d’impact', 'M4 20V10m6 10V4m6 16v-7m4 7H2'],
        ['admin.impact-categories.index', 'admin.impact-categories.*', 'Catégories d’impact', 'M12 3c3 3 5 5.5 5 9a5 5 0 1 1-10 0c0-3.5 2-6 5-9Z'],
        ['admin.impact-metrics.index', 'admin.impact-metrics.*', 'Facteurs matières', 'M4 6h16M4 12h16M4 18h10'],
    ]],
    ['label' => 'Seconde vie', 'dot' => 'bg-amber-500', 'active' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300', 'icon_active' => 'text-amber-600 dark:text-amber-400', 'items' => [
        ['admin.repair-orders.index', 'admin.repair-orders.*', 'Réparations', 'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6Z'],
        ['admin.marketplace.index', 'admin.marketplace.*', 'Marketplace', 'M3 9l1.5-5h15L21 9M3 9h18M3 9v11h18V9M9 20v-6h6v6'],
        ['admin.trade-ins.index', 'admin.trade-ins.*', 'Reprises', 'M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4'],
        ['admin.repair-centers.index', 'admin.repair-centers.*', 'Centres de réparation', 'M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z'],
    ]],
];
@endphp
@foreach($moduleGroups as $group)
    <div>
        <p class="mb-2 flex items-center gap-2 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-400"><span class="h-2 w-2 rounded-full {{ $group['dot'] }}"></span>{{ $group['label'] }}</p>
        <div class="space-y-1">
            @foreach($group['items'] as [$route, $pattern, $label, $icon])
                <a href="{{ route($route) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($pattern) ? $group['active'] : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white' }}">
                    <svg class="h-5 w-5 {{ request()->routeIs($pattern) ? $group['icon_active'] : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>{{ $label }}
                </a>
            @endforeach
        </div>
    </div>
@endforeach
