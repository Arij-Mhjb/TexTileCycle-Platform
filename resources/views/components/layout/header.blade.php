<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl transition-colors dark:border-slate-800 dark:bg-slate-950/90">
 <div class="flex h-20 items-center justify-between px-5 sm:px-8">
  <div><p class="text-xs font-semibold text-slate-400">{{ now()->translatedFormat('l d F Y') }}</p><h1 class="text-lg font-black tracking-tight text-slate-900 dark:text-white">{{ $title ?? 'Espace de gestion' }}</h1></div>
  <div class="flex items-center gap-3">
   <a href="{{ route('scans.create') }}" class="hidden rounded-xl bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-700 transition hover:-translate-y-0.5 dark:bg-emerald-950 dark:text-emerald-300 sm:inline-flex">+ Nouveau scan</a>
   <x-theme-toggle />
   <div class="relative" x-data="{open:false}"><button @click="open=!open" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-1.5 pr-3 transition-colors dark:border-slate-700 dark:bg-slate-900"><span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-900 text-sm font-black text-white dark:bg-emerald-600">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="hidden text-left sm:block"><span class="block text-sm font-bold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</span><span class="block text-xs capitalize text-slate-400">{{ auth()->user()->role }}</span></span></button>
    <div x-cloak x-show="open" @click.outside="open=false" x-transition class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900"><a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Mon profil</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">Déconnexion</button></form></div>
   </div>
  </div>
 </div>
 <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-5 py-2 dark:border-slate-800 lg:hidden">
  @foreach([
   ['admin.dashboard','Pilotage'],['products.index','Produits'],['scans.index','Scans'],
   ['recovery-programs.index','Programmes'],['product-returns.index','Retours'],['assessments.index','Conformité']
  ] as [$routeName,$label])
   <a href="{{ route($routeName) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-bold {{ request()->routeIs(str($routeName)->before('.').'.*') || request()->routeIs($routeName) ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:bg-slate-50' }}">{{ $label }}</a>
  @endforeach
 </nav>
</header>
