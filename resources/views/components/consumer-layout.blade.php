@props(['title' => 'Mon espace'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f8f7] text-slate-800 transition-colors duration-300 dark:bg-slate-950 dark:text-slate-200">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl transition-colors dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
            <x-application-logo />
            <nav aria-label="Navigation principale" class="hidden items-center gap-1 xl:flex">
                @foreach([
                    ['consumer.home','Accueil'],['consumer.products','Vêtements'],['consumer.programs','Solutions'],
                    ['consumer.points','Points de collecte'],['scans.index','Mes scans'],['product-returns.index','Mes retours']
                ] as [$routeName,$label])
                    <a href="{{ route($routeName) }}" class="rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ request()->routeIs($routeName) ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="relative" x-data="{ open: false }">
                <div class="flex items-center gap-2">
                <x-theme-toggle />
                <button @click="open = !open" @keydown.escape="open = false" :aria-expanded="open" aria-label="Menu du compte" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-1.5 pr-3 shadow-sm transition-colors dark:border-slate-700 dark:bg-slate-900">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-600 text-sm font-black text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="hidden max-w-28 truncate text-left 2xl:block"><span class="block truncate text-sm font-bold dark:text-white">{{ auth()->user()->name }}</span><span class="block text-[11px] text-slate-400">Membre TexTileCycle</span></span>
                </button>
                </div>
                <div x-cloak x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <a href="{{ route('consumer.programs') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 xl:hidden">Solutions circulaires</a>
                    <a href="{{ route('scans.index') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 xl:hidden">Mes scans</a>
                    <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">Mon profil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">Déconnexion</button></form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto min-h-[calc(100vh-10rem)] max-w-7xl px-5 py-8 pb-28 sm:px-8 xl:pb-10">
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800"><span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-600 text-white">✓</span>{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><p class="font-black">Veuillez corriger les informations.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot }}
    </main>

    <div class="pb-20 xl:pb-0"><x-layout.footer /></div>
    <nav aria-label="Navigation mobile" class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-slate-200 bg-white/95 px-2 pt-2 pb-[max(.5rem,env(safe-area-inset-bottom))] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/95 xl:hidden">
        @foreach([
            ['consumer.home','home','Accueil'],['consumer.products','search','Explorer'],['scans.create','scan','Scanner'],
            ['consumer.points','map-pin','Collecte'],['product-returns.index','refresh','Retours']
        ] as [$routeName,$icon,$label])
            <a href="{{ route($routeName) }}" class="flex flex-col items-center gap-1 rounded-xl py-1.5 text-[10px] font-bold {{ request()->routeIs($routeName) ? 'text-emerald-600' : 'text-slate-400' }}"><x-icon :name="$icon" class="h-5 w-5" />{{ $label }}</a>
        @endforeach
    </nav>
</body>
</html>
