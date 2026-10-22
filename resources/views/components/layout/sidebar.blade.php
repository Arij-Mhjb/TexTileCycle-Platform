@php
$groups = [
 ['label'=>'Validations','description'=>'Dossiers à examiner','icon'=>'M9 12l2 2 4-4M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z','items'=>[
  ['route'=>'admin.brand-applications.index','active'=>'admin.brand-applications.*','label'=>'Inscriptions marques','icon'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
  ['route'=>'admin.passport-proposals.index','active'=>'admin.passport-proposals.*','label'=>'Passeports proposés','icon'=>'M7 3h10a2 2 0 0 1 2 2v16l-7-3-7 3V5a2 2 0 0 1 2-2Z'],
  ['route'=>'admin.roadmaps.index','active'=>'admin.roadmaps.*','label'=>'Étapes des roadmaps','icon'=>'M4 19V5m0 0h7l2 3h7v8h-7l-2-3H4'],
  ['route'=>'evidences.index','active'=>'evidences.*','label'=>'Preuves déposées','icon'=>'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8M8 17h5'],
  ['route'=>'admin.material-reviews.index','active'=>'admin.material-reviews.*','label'=>'Matières collectées','icon'=>'M9 12l2 2 4-4M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z'],
  ['route'=>'assessments.index','active'=>'assessments.*','label'=>'Évaluations','icon'=>'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
 ]],
 ['label'=>'Catalogue & labels','description'=>'Référentiel textile','icon'=>'M4 6h16M4 12h16M4 18h10','items'=>[
  ['route'=>'products.index','active'=>'products.*','label'=>'Produits','icon'=>'M6 3h12l3 4-3 3v11H6V10L3 7l3-4Z'],
  ['route'=>'materials.index','active'=>'materials.*','label'=>'Matières','icon'=>'M12 3c3 3 5 5.5 5 9a5 5 0 1 1-10 0c0-3.5 2-6 5-9Z'],
  ['route'=>'admin.labels.index','active'=>'admin.labels.*','label'=>'Labels écologiques','icon'=>'M12 3l2.5 5 5.5.8-4 4 .9 5.7-4.9-2.7-4.9 2.7.9-5.7-4-4 5.5-.8L12 3Z'],
  ['route'=>'scans.index','active'=>'scans.*','label'=>'Smart scans','icon'=>'M4 7V4h3M17 4h3v3M20 17v3h-3M7 20H4v-3M8 8h8v8H8z'],
  ['route'=>'detections.index','active'=>'detections.*','label'=>'Détections','icon'=>'M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z'],
 ]],
 ['label'=>'Blog','description'=>'Sensibilisation & contenus','icon'=>'M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15Z','items'=>[
  ['route'=>'admin.blog-stats.index','active'=>'admin.blog-stats.*','label'=>'Statistiques','icon'=>'M4 20V10m6 10V4m6 16v-7m4 7H2'],
  ['route'=>'admin.categories.index','active'=>'admin.categories.*','label'=>'Catégories','icon'=>'M4 6h16M4 12h16M4 18h10'],
  ['route'=>'admin.articles.index','active'=>'admin.articles.*','label'=>'Articles','icon'=>'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Zm0 0v6h6M8 13h8M8 17h5'],
 ]],
 ['label'=>'Assistant virtuel','description'=>'Chatbot questions/réponses','icon'=>'M8 10h8M8 14h5M21 12c0 4.4-4 8-9 8-1.3 0-2.5-.2-3.6-.6L3 21l1.3-4.3C3.5 15.3 3 13.7 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z','items'=>[
  ['route'=>'admin.chatbot-stats.index','active'=>'admin.chatbot-stats.*','label'=>'Statistiques','icon'=>'M4 20V10m6 10V4m6 16v-7m4 7H2'],
  ['route'=>'admin.chatbot-categories.index','active'=>'admin.chatbot-categories.*','label'=>'Catégories','icon'=>'M4 6h16M4 12h16M4 18h10'],
  ['route'=>'admin.chatbot-questions.index','active'=>'admin.chatbot-questions.*','label'=>'Questions/Réponses','icon'=>'M8 10h8M8 14h5M21 12c0 4.4-4 8-9 8-1.3 0-2.5-.2-3.6-.6L3 21l1.3-4.3C3.5 15.3 3 13.7 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z'],
 ]],
 ['label'=>'Collecte & circularité','description'=>'Flux de récupération','icon'=>'M20 7h-5V2M4 17h5v5M19 17a8 8 0 0 1-13 2M5 7A8 8 0 0 1 18 5','items'=>[
  ['route'=>'product-returns.index','active'=>'product-returns.*','label'=>'Retours textiles','icon'=>'M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3'],
  ['route'=>'recovery-programs.index','active'=>'recovery-programs.*','label'=>'Programmes de collecte','icon'=>'M20 7h-5V2M4 17h5v5M19 17a8 8 0 0 1-13 2M5 7A8 8 0 0 1 18 5'],
  ['route'=>'collection-points.index','active'=>'collection-points.*','label'=>'Points de collecte','icon'=>'M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z M12 7v6m-3-3h6'],
  ['route'=>'admin.collectors.index','active'=>'admin.collectors.*','label'=>'Collecteurs','icon'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87'],
  ['route'=>'partners.index','active'=>'partners.*','label'=>'Partenaires circulaires','icon'=>'M4 7h16v10H4z M8 7V4h8v3M8 12h8M8 16h5'],
  ['route'=>'treatment-results.index','active'=>'treatment-results.*','label'=>'Traitements','icon'=>'m4 14 6 6L20 10M14 4l6 6-6 6'],
 ]],
 ['label'=>'Traçabilité fournisseurs','description'=>'Lots et conformité','icon'=>'M3 21h18M5 21V7l7-4 7 4v14','items'=>[
  ['route'=>'admin.suppliers.index','active'=>'admin.suppliers.*','label'=>'Fournisseurs & lots','icon'=>'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6'],
  ['route'=>'admin.traceability.index','active'=>'admin.traceability.*','label'=>'Conformité des lots','icon'=>'M9 12l2 2 4-4M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z'],
 ]],
 ['label'=>'Impact environnemental','description'=>'Mesure et rapports ESG','icon'=>'M4 20V10m6 10V4m6 16v-7m4 7H2','items'=>[
  ['route'=>'admin.impact-reports.index','active'=>'admin.impact-reports.*','label'=>'Rapports d’impact','icon'=>'M4 20V10m6 10V4m6 16v-7m4 7H2'],
  ['route'=>'admin.impact-categories.index','active'=>'admin.impact-categories.*','label'=>'Catégories d’impact','icon'=>'M12 3c3 3 5 5.5 5 9a5 5 0 1 1-10 0c0-3.5 2-6 5-9Z'],
  ['route'=>'admin.impact-metrics.index','active'=>'admin.impact-metrics.*','label'=>'Facteurs matières','icon'=>'M4 6h16M4 12h16M4 18h10'],
 ]],
 ['label'=>'Seconde vie','description'=>'Réparation et réemploi','icon'=>'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3','items'=>[
  ['route'=>'admin.repair-orders.index','active'=>'admin.repair-orders.*','label'=>'Demandes de réparation','icon'=>'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4'],
  ['route'=>'admin.repair-centers.index','active'=>'admin.repair-centers.*','label'=>'Centres de réparation','icon'=>'M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z'],
  ['route'=>'admin.repair-partners.index','active'=>'admin.repair-partners.*','label'=>'Ateliers partenaires','icon'=>'M12 3v18M3 12h18M7 7h10v10H7z'],
  ['route'=>'admin.marketplace.index','active'=>'admin.marketplace.*','label'=>'Marketplace','icon'=>'M3 9l1.5-5h15L21 9M3 9h18M3 9v11h18V9'],
  ['route'=>'admin.trade-ins.index','active'=>'admin.trade-ins.*','label'=>'Reprises','icon'=>'M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4'],
 ]],
 ['label'=>'Utilisateurs & récompenses','description'=>'Support et fidélité','icon'=>'M16 21v-2a4 4 0 0 0-4-4H6','items'=>[
  ['route'=>'admin.consumer-claims.index','active'=>'admin.consumer-claims.*','label'=>'Réclamations Consumer','icon'=>'M12 9v4m0 4h.01M10.3 3.6 2.6 17h18.8l-7.7-17a1.9 1.9 0 0 0-3.4 0Z'],
  ['route'=>'admin.reward-items.index','active'=>'admin.reward-items.*','label'=>'Catalogue récompenses','icon'=>'M20 12v8H4v-8m16 0H4m16 0V8H4v4m8 8V8m-5 0c-2-2-1-5 1-5 2 0 4 3 4 5m5 0c2-2 1-5-1-5-2 0-4 3-4 5'],
 ]],
];
@endphp
@php($adminInterface = auth()->user()?->isAdmin() ?? false)
<aside @class(['fixed inset-y-0 left-0 z-40 hidden w-72 border-r transition-colors dark:border-slate-800 dark:bg-slate-950 lg:flex lg:flex-col', 'border-indigo-900 bg-indigo-950' => $adminInterface, 'border-slate-200 bg-white' => ! $adminInterface])>
 <div class="flex h-20 items-center border-b px-6 {{ $adminInterface ? 'border-white/10 bg-white' : 'border-slate-100 dark:border-slate-800' }}"><x-application-logo /></div>
 <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Navigation administrateur">
  <a href="{{ route('admin.dashboard') }}" class="mb-4 flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold transition {{ request()->routeIs('admin.dashboard') ? 'bg-white text-indigo-950 shadow-lg' : 'text-indigo-100 hover:bg-white/10 hover:text-white' }}">
   <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z"/></svg>
   Centre de pilotage
  </a>
  <div class="space-y-2">
   @foreach($groups as $group)
    @php($groupActive = collect($group['items'])->contains(fn ($link) => request()->routeIs($link['active'])))
    <section x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }" class="rounded-2xl border border-white/10 bg-white/[.04]">
     <button type="button" @click="open = !open" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left text-white transition hover:bg-white/[.06]" :aria-expanded="open.toString()">
      <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $groupActive ? 'bg-white/20 text-white' : 'bg-indigo-900 text-indigo-300' }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $group['icon'] }}"/></svg></span>
      <span class="min-w-0 flex-1"><span class="block text-sm font-bold">{{ $group['label'] }}</span><span class="block truncate text-[10px] font-medium text-indigo-300">{{ $group['description'] }}</span></span>
      <svg class="h-4 w-4 shrink-0 text-indigo-300 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
     </button>
     <div x-cloak x-show="open" x-transition.opacity class="space-y-1 px-2 pb-2">
      @foreach($group['items'] as $link)
       <a href="{{ route($link['route']) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs($link['active']) ? 'bg-white/15 text-white shadow-sm' : 'text-indigo-100 hover:bg-white/10 hover:text-white' }}"><svg class="h-4 w-4 {{ request()->routeIs($link['active']) ? 'text-white' : 'text-indigo-400 group-hover:text-indigo-200' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg>{{ $link['label'] }}</a>
      @endforeach
     </div>
    </section>
   @endforeach
  </div>
 </nav>
 <div class="border-t p-4 {{ $adminInterface ? 'border-white/10' : 'border-slate-100 dark:border-slate-800' }}"><div class="relative overflow-hidden rounded-2xl {{ $adminInterface ? 'bg-indigo-900 ring-1 ring-white/10' : 'bg-slate-900' }} p-4 text-white"><div class="absolute -right-6 -top-6 h-20 w-20 rounded-full {{ $adminInterface ? 'bg-violet-400/30' : 'bg-emerald-500/20' }} blur-xl"></div><p class="relative text-xs font-semibold {{ $adminInterface ? 'text-indigo-200' : 'text-emerald-300' }}">{{ $adminInterface ? 'Centre de contrôle' : 'Impact suivi' }}</p><p class="relative mt-1 text-sm font-bold">{{ $adminInterface ? 'Pilotage administrateur' : 'Chaque retour compte' }}</p><p class="relative mt-1 text-xs leading-5 text-slate-400">{{ $adminInterface ? 'Examinez les dossiers, preuves et opérations de la plateforme.' : 'Tracez le parcours complet du textile jusqu’à sa seconde vie.' }}</p></div></div>
</aside>

