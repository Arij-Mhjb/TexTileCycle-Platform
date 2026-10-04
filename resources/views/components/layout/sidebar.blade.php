@php
$groups = [
 ['label'=>'Vue générale','items'=>[['route'=>'admin.dashboard','active'=>'admin.dashboard','label'=>'Centre de pilotage','icon'=>'M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z']]],
 ['label'=>'Passeport textile','items'=>[
  ['route'=>'products.index','active'=>'products.*','label'=>'Produits','icon'=>'M6 3h12l3 4-3 3v11H6V10L3 7l3-4Z'],
  ['route'=>'materials.index','active'=>'materials.*','label'=>'Matières','icon'=>'M12 3c3 3 5 5.5 5 9a5 5 0 1 1-10 0c0-3.5 2-6 5-9Z'],
  ['route'=>'scans.index','active'=>'scans.*','label'=>'Smart scans','icon'=>'M4 7V4h3M17 4h3v3M20 17v3h-3M7 20H4v-3M8 8h8v8H8z'],
  ['route'=>'detections.index','active'=>'detections.*','label'=>'Détections','icon'=>'M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z'],
 ]],
 ['label'=>'Circularité','items'=>[
  ['route'=>'recovery-programs.index','active'=>'recovery-programs.*','label'=>'Programmes','icon'=>'M20 7h-5V2M4 17h5v5M19 17a8 8 0 0 1-13 2M5 7A8 8 0 0 1 18 5'],
  ['route'=>'collection-points.index','active'=>'collection-points.*','label'=>'Points de collecte','icon'=>'M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z M12 7v6m-3-3h6'],
  ['route'=>'product-returns.index','active'=>'product-returns.*','label'=>'Retours','icon'=>'M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3'],
  ['route'=>'treatment-results.index','active'=>'treatment-results.*','label'=>'Traitements','icon'=>'m4 14 6 6L20 10M14 4l6 6-6 6'],
 ]],
 ['label'=>'Conformité','items'=>[
  ['route'=>'assessments.index','active'=>'assessments.*','label'=>'Évaluations','icon'=>'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
  ['route'=>'evidences.index','active'=>'evidences.*','label'=>'Preuves','icon'=>'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8M8 17h5'],
 ]],
];
@endphp
<aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-slate-200 bg-white transition-colors dark:border-slate-800 dark:bg-slate-950 lg:flex lg:flex-col">
 <div class="flex h-20 items-center border-b border-slate-100 px-6 dark:border-slate-800"><x-application-logo /></div>
 <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
  @foreach($groups as $group)<div><p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-slate-400">{{ $group['label'] }}</p><div class="space-y-1">
   @foreach($group['items'] as $link)<a href="{{ route($link['route']) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($link['active']) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white' }}"><svg class="h-5 w-5 {{ request()->routeIs($link['active']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg>{{ $link['label'] }}</a>@endforeach
  </div></div>@endforeach
 </nav>
 <div class="border-t border-slate-100 p-4 dark:border-slate-800"><div class="relative overflow-hidden rounded-2xl bg-slate-900 p-4 text-white"><div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-emerald-500/20 blur-xl"></div><p class="relative text-xs font-semibold text-emerald-300">Impact suivi</p><p class="relative mt-1 text-sm font-bold">Chaque retour compte</p><p class="relative mt-1 text-xs leading-5 text-slate-400">Tracez le parcours complet du textile jusqu’à sa seconde vie.</p></div></div>
</aside>
