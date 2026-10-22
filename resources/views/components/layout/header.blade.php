@php($adminInterface = auth()->user()?->isAdmin() ?? false)
<header @class(['sticky top-0 z-30 border-b backdrop-blur-xl transition-colors dark:border-slate-800 dark:bg-slate-950/90', 'border-indigo-200/80 bg-indigo-50/95' => $adminInterface, 'border-slate-200/80 bg-white/90' => ! $adminInterface])>
 <div class="flex h-20 items-center justify-between px-5 sm:px-8">
  <div><div class="flex items-center gap-2"><p class="text-xs font-semibold text-slate-400">{{ now()->translatedFormat('l d F Y') }}</p>@if($adminInterface)<span class="rounded-full bg-indigo-700 px-2.5 py-1 text-[9px] font-black uppercase tracking-[.16em] text-white">Administration</span>@endif</div><h1 class="mt-1 text-lg font-black tracking-tight {{ $adminInterface ? 'text-indigo-950' : 'text-slate-900' }} dark:text-white">{{ $title ?? 'Espace de gestion' }}</h1></div>
  <div class="flex items-center gap-3">
   <a href="{{ route('scans.create') }}" class="hidden rounded-xl px-3 py-2 text-sm font-bold transition hover:-translate-y-0.5 sm:inline-flex {{ $adminInterface ? 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200' : 'bg-emerald-50 text-emerald-700' }}">+ Nouveau scan</a>
   <x-module.notification-bell />
   <x-theme-toggle />
   @auth
   <div class="relative" x-data="{open:false}"><button @click="open=!open" class="flex items-center gap-3 rounded-xl border bg-white p-1.5 pr-3 transition-colors dark:border-slate-700 dark:bg-slate-900 {{ $adminInterface ? 'border-indigo-200' : 'border-slate-200' }}"><span class="grid h-9 w-9 place-items-center rounded-lg text-sm font-black text-white {{ $adminInterface ? 'bg-indigo-700' : 'bg-slate-900 dark:bg-emerald-600' }}">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="hidden text-left sm:block"><span class="block text-sm font-bold text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</span><span class="block text-xs capitalize {{ $adminInterface ? 'text-indigo-500' : 'text-slate-400' }}">{{ $adminInterface ? 'Administrateur' : auth()->user()->role }}</span></span></button>
    <div x-cloak x-show="open" @click.outside="open=false" x-transition class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900"><a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Mon profil</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">Déconnexion</button></form></div>
   </div>
   @else
   <a href="{{ route('login') }}" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-bold text-white transition hover:-translate-y-0.5 dark:bg-emerald-600">Connexion</a>
   @endauth
  </div>
 </div>
 <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-5 py-2 dark:border-slate-800 lg:hidden">
  @foreach([
   ['admin.dashboard','Pilotage'],['products.index','Produits'],['scans.index','Scans'],
   ['recovery-programs.index','Programmes'],['product-returns.index','Retours'],['assessments.index','Conformité']
  ] as [$routeName,$label])
   <a href="{{ route($routeName) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-bold {{ request()->routeIs(str($routeName)->before('.').'.*') || request()->routeIs($routeName) ? ($adminInterface ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-50 text-emerald-700') : 'text-slate-500 hover:bg-slate-50' }}">{{ $label }}</a>
  @endforeach
 </nav>
</header>
