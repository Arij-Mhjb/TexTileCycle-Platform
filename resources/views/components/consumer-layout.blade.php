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
            <nav aria-label="Navigation principale" class="hidden items-center gap-1 lg:flex">
                <a href="{{ route('consumer.home') }}" class="rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ request()->routeIs('consumer.home') ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:bg-slate-50' }}">Accueil</a>
                <a href="{{ route('consumer.products') }}" class="rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ request()->routeIs('consumer.products*') ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:bg-slate-50' }}">Explorer</a>
                <a href="{{ route('consumer.marketplace.index') }}" class="rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ request()->routeIs('consumer.marketplace.*') ? 'bg-amber-50 text-amber-800' : 'text-amber-700 hover:bg-amber-50' }}">Boutique</a>
                <a href="{{ route('consumer.community.index') }}" class="rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ request()->routeIs('consumer.community.*') ? 'bg-violet-50 text-violet-700' : 'text-slate-500 hover:bg-violet-50' }}">Communauté</a>
                <a href="{{ route('blog.index') }}" class="rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ request()->routeIs('blog.*') ? 'bg-emerald-50 text-emerald-700' : 'text-slate-500 hover:bg-emerald-50' }}">Blog</a>
                <div class="relative" x-data="{ activitiesOpen: false }">
                    <button type="button" @click="activitiesOpen = !activitiesOpen" @keydown.escape.window="activitiesOpen = false" class="flex items-center gap-1 rounded-xl px-3.5 py-2.5 text-sm font-bold text-slate-500 transition hover:bg-slate-50">Mon espace <span aria-hidden="true">⌄</span></button>
                    <div x-cloak x-show="activitiesOpen" @click.outside="activitiesOpen = false" x-transition class="absolute left-0 top-full z-50 mt-2 grid w-56 gap-1 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                        <a href="{{ route('consumer.wardrobe') }}" class="rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">Ma garde-robe</a>
                        <a href="{{ route('scans.index') }}" class="rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">Mes scans</a>
                        <a href="{{ route('product-returns.index') }}" class="rounded-xl px-3 py-2 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">Mes retours</a>
                        <a href="{{ route('consumer.repairs.index') }}" class="rounded-xl px-3 py-2 text-sm font-bold hover:bg-amber-50">Mes réparations</a>
                        <a href="{{ route('consumer.trade-ins.index') }}" class="rounded-xl px-3 py-2 text-sm font-bold hover:bg-amber-50">Reprise contre points</a>
                        <a href="{{ route('consumer.wallet') }}" class="rounded-xl px-3 py-2 text-sm font-bold hover:bg-emerald-50">Wallet et récompenses</a>
                        <a href="{{ route('consumer.programs') }}" class="rounded-xl px-3 py-2 text-sm font-bold hover:bg-emerald-50">Solutions circulaires</a>
                    </div>
                </div>
            </nav>
            <div class="relative" x-data="{ open: false }">
                <div class="flex items-center gap-2">
                <x-module.notification-bell />
                <x-theme-toggle />
                <button @click="open = !open" @keydown.escape="open = false" :aria-expanded="open" aria-label="Menu du compte" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-1.5 pr-3 shadow-sm transition-colors dark:border-slate-700 dark:bg-slate-900">
                    <span class="grid h-9 w-9 place-items-center overflow-hidden rounded-xl bg-emerald-600 text-sm font-black text-white">@if(auth()->user()->profile_photo_url)<img src="{{ auth()->user()->profile_photo_url }}" alt="" class="h-full w-full object-cover">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</span>
                    <span class="hidden max-w-28 truncate text-left 2xl:block"><span class="block truncate text-sm font-bold dark:text-white">{{ auth()->user()->name }}</span><span class="block text-[11px] text-slate-400">Membre TexTileCycle</span></span>
                </button>
                </div>
                <div x-cloak x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-52 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    <a href="{{ route('consumer.programs') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 lg:hidden">Solutions circulaires</a>
                    <a href="{{ route('consumer.wardrobe') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 lg:hidden">Ma garde-robe</a>
                    <a href="{{ route('scans.index') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 lg:hidden">Mes scans</a>
                    <a href="{{ route('consumer.wallet') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 lg:hidden">Wallet et récompenses</a>
                    <a href="{{ route('blog.index') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 lg:hidden">Blog</a>
                    <a href="{{ route('consumer.notifications.index') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">Toutes mes notifications</a>
                    <a href="{{ route('consumer.claims.index') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">Aide et réclamations</a>
                    @include('modules.partials.consumer-menu')
                    <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800">Mon profil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40">Déconnexion</button></form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto min-h-[calc(100vh-10rem)] max-w-7xl px-5 py-8 pb-28 sm:px-8 xl:pb-10">
        @if(session('success'))<div data-flash-message data-flash-type="success" class="hidden">{{ session('success') }}</div>@endif
        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><p class="font-black">Veuillez corriger les informations.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot }}
    </main>

    <div class="pb-20 xl:pb-0"><x-layout.footer /></div>
    <nav aria-label="Navigation mobile" class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-slate-200 bg-white/95 px-2 pt-2 pb-[max(.5rem,env(safe-area-inset-bottom))] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/95 xl:hidden">
        @foreach([
            ['consumer.home','home','Accueil'],['consumer.products','search','Explorer'],['scans.create','scan','Scanner'],
            ['consumer.community.index','users','Communauté'],['consumer.marketplace.index','shopping-bag','Boutique']
        ] as [$routeName,$icon,$label])
            <a href="{{ route($routeName) }}" class="flex flex-col items-center gap-1 rounded-xl py-1.5 text-[10px] font-bold {{ request()->routeIs($routeName) ? 'text-emerald-600' : 'text-slate-400' }}"><x-icon :name="$icon" class="h-5 w-5" />{{ $label }}</a>
        @endforeach
    </nav>
</body>
</html>
