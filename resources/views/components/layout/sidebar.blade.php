@php
$groups = [
 ['label'=>'Vue générale','items'=>[['route'=>'admin.dashboard','active'=>'admin.dashboard','label'=>'Centre de pilotage','icon'=>'M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z']]],
 ['label'=>'Passeport textile','items'=>[
  ['route'=>'products.index','active'=>'products.*','label'=>'Produits','icon'=>'M6 3h12l3 4-3 3v11H6V10L3 7l3-4Z'],
  ['route'=>'materials.index','active'=>'materials.*','label'=>'Matières','icon'=>'M12 3c3 3 5 5.5 5 9a5 5 0 1 1-10 0c0-3.5 2-6 5-9Z'],
  ['route'=>'scans.index','active'=>'scans.*','label'=>'Smart scans','icon'=>'M4 7V4h3M17 4h3v3M20 17v3h-3M7 20H4v-3M8 8h8v8H8z'],
  ['route'=>'admin.passport-proposals.index','active'=>'admin.passport-proposals.*','label'=>'Propositions de passeport','icon'=>'M7 3h10a2 2 0 0 1 2 2v16l-7-3-7 3V5a2 2 0 0 1 2-2Z'],
  ['route'=>'detections.index','active'=>'detections.*','label'=>'Détections','icon'=>'M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z'],
 ]],
 ['label'=>'Circularité','items'=>[
  ['route'=>'recovery-programs.index','active'=>'recovery-programs.*','label'=>'Programmes','icon'=>'M20 7h-5V2M4 17h5v5M19 17a8 8 0 0 1-13 2M5 7A8 8 0 0 1 18 5'],
  ['route'=>'collection-points.index','active'=>'collection-points.*','label'=>'Points de collecte','icon'=>'M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z M12 7v6m-3-3h6'],
  ['route'=>'partners.index','active'=>'partners.*','label'=>'Partenaires circulaires','icon'=>'M4 7h16v10H4z M8 7V4h8v3M8 12h8M8 16h5'],
  ['route'=>'admin.repair-partners.index','active'=>'admin.repair-partners.*','label'=>'Ateliers & partenaires','icon'=>'M12 3v18M3 12h18M7 7h10v10H7z'],
  ['route'=>'admin.collectors.index','active'=>'admin.collectors.*','label'=>'Collecteurs','icon'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87'],
  ['route'=>'product-returns.index','active'=>'product-returns.*','label'=>'Retours','icon'=>'M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3'],
  ['route'=>'admin.material-reviews.index','active'=>'admin.material-reviews.*','label'=>'Contrôle matières','icon'=>'M9 12l2 2 4-4M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z'],
  ['route'=>'treatment-results.index','active'=>'treatment-results.*','label'=>'Traitements','icon'=>'m4 14 6 6L20 10M14 4l6 6-6 6'],
  ['route'=>'admin.consumer-claims.index','active'=>'admin.consumer-claims.*','label'=>'Réclamations Consumer','icon'=>'M12 9v4m0 4h.01M10.3 3.6 2.6 17h18.8l-7.7-17a1.9 1.9 0 0 0-3.4 0Z'],
  ['route'=>'admin.reward-items.index','active'=>'admin.reward-items.*','label'=>'Catalogue récompenses','icon'=>'M20 12v8H4v-8m16 0H4m16 0V8H4v4m8 8V8m-5 0c-2-2-1-5 1-5 2 0 4 3 4 5m5 0c2-2 1-5-1-5-2 0-4 3-4 5'],
 ]],
 ['label'=>'Conformité','items'=>[
  ['route'=>'admin.brand-applications.index','active'=>'admin.brand-applications.*','label'=>'Inscriptions marques','icon'=>'M9 12l2 2 4-4M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z'],
  ['route'=>'admin.roadmaps.index','active'=>'admin.roadmaps.*','label'=>'Feuilles de route','icon'=>'M9 12l2 2 4-4M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z'],
  ['route'=>'admin.labels.index','active'=>'admin.labels.*','label'=>'Labels','icon'=>'M12 3l2.5 5 5.5.8-4 4 .9 5.7-4.9-2.7-4.9 2.7.9-5.7-4-4 5.5-.8L12 3Z'],
  ['route'=>'assessments.index','active'=>'assessments.*','label'=>'Évaluations','icon'=>'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
  ['route'=>'evidences.index','active'=>'evidences.*','label'=>'Preuves','icon'=>'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8M8 17h5'],
 ]],
];
@endphp
@php($adminInterface = auth()->user()?->isAdmin() ?? false)
<aside @class(['fixed inset-y-0 left-0 z-40 hidden w-72 border-r transition-colors dark:border-slate-800 dark:bg-slate-950 lg:flex lg:flex-col', 'border-indigo-900 bg-indigo-950' => $adminInterface, 'border-slate-200 bg-white' => ! $adminInterface])>
 <div class="flex h-20 items-center border-b px-6 {{ $adminInterface ? 'border-white/10 bg-white' : 'border-slate-100 dark:border-slate-800' }}"><x-application-logo /></div>
 <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
  @foreach($groups as $group)<div><p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.18em] {{ $adminInterface ? 'text-indigo-300' : 'text-slate-400' }}">{{ $group['label'] }}</p><div class="space-y-1">
   @foreach($group['items'] as $link)<a href="{{ route($link['route']) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($link['active']) ? ($adminInterface ? 'bg-white/15 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300') : ($adminInterface ? 'text-indigo-100 hover:bg-white/10 hover:text-white' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white') }}"><svg class="h-5 w-5 {{ request()->routeIs($link['active']) ? ($adminInterface ? 'text-indigo-200' : 'text-emerald-600 dark:text-emerald-400') : ($adminInterface ? 'text-indigo-400 group-hover:text-indigo-200' : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg>{{ $link['label'] }}</a>@endforeach
  </div></div>@endforeach
  @include('modules.partials.admin-sidebar')
 </nav>
 <div class="border-t p-4 {{ $adminInterface ? 'border-white/10' : 'border-slate-100 dark:border-slate-800' }}"><div class="relative overflow-hidden rounded-2xl {{ $adminInterface ? 'bg-indigo-900 ring-1 ring-white/10' : 'bg-slate-900' }} p-4 text-white"><div class="absolute -right-6 -top-6 h-20 w-20 rounded-full {{ $adminInterface ? 'bg-violet-400/30' : 'bg-emerald-500/20' }} blur-xl"></div><p class="relative text-xs font-semibold {{ $adminInterface ? 'text-indigo-200' : 'text-emerald-300' }}">{{ $adminInterface ? 'Centre de contrôle' : 'Impact suivi' }}</p><p class="relative mt-1 text-sm font-bold">{{ $adminInterface ? 'Pilotage administrateur' : 'Chaque retour compte' }}</p><p class="relative mt-1 text-xs leading-5 text-slate-400">{{ $adminInterface ? 'Examinez les dossiers, preuves et opérations de la plateforme.' : 'Tracez le parcours complet du textile jusqu’à sa seconde vie.' }}</p></div></div>
</aside>

