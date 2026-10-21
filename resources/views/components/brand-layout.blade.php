<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ config('app.name') }} · Marque</title>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-6 py-4" x-data="{ navigationOpen: false }">
            <a href="{{ route('brand.dashboard') }}" class="text-xl font-black text-emerald-700">
                TexTileCycle <span class="text-sm font-medium text-slate-500">Brand Studio</span>
            </a>

            <div
                id="brand-navigation"
                class="order-3 hidden w-full lg:order-none lg:!flex lg:w-auto lg:flex-1 lg:items-center"
                :class="navigationOpen ? '!block' : 'hidden'"
            >
                <nav class="grid gap-1 border-t border-slate-100 pt-4 lg:ml-6 lg:flex lg:items-center lg:border-0 lg:pt-0" aria-label="Navigation de l’espace marque">
                    <a href="{{ route('brand.dashboard') }}" class="rounded-xl px-3 py-2 text-sm font-bold transition {{ request()->routeIs('brand.dashboard') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-slate-100' }}">Pilotage</a>

                    <x-brand-nav-dropdown label="Produits" :active="request()->routeIs('brand.products.*', 'brand.product-label-recommendations.*')">
                        <a role="menuitem" href="{{ route('brand.products.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Catalogue</span><span class="block text-xs text-slate-500">Consulter et gérer les produits</span></a>
                        <a role="menuitem" href="{{ route('brand.products.create') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Nouveau produit</span><span class="block text-xs text-slate-500">Créer un passeport produit</span></a>
                        <a role="menuitem" href="{{ route('brand.products.import') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Import CSV</span><span class="block text-xs text-slate-500">Importer plusieurs produits</span></a>
                        <a role="menuitem" href="{{ route('brand.product-label-recommendations.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-violet-50"><span class="block text-sm font-bold text-violet-800">IA produit-label</span><span class="block text-xs text-slate-500">Analyser les labels compatibles</span></a>
                    </x-brand-nav-dropdown>

                    <x-brand-nav-dropdown label="Labels" :active="request()->routeIs('brand.labels.*', 'brand.roadmaps.*', 'brand.assessments.*')">
                        <a role="menuitem" href="{{ route('brand.labels.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Preuves et référentiels</span><span class="block text-xs text-slate-500">Préparer les dossiers de labels</span></a>
                        <a role="menuitem" href="{{ route('brand.roadmaps.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Feuilles de route</span><span class="block text-xs text-slate-500">Suivre les objectifs et échéances</span></a>
                    </x-brand-nav-dropdown>

                    <x-brand-nav-dropdown label="Circularité" :active="request()->routeIs('brand.material-requests.*', 'brand.material-matches.*')">
                        <a role="menuitem" href="{{ route('brand.material-requests.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Matières recherchées</span><span class="block text-xs text-slate-500">Déclarer les besoins de la marque</span></a>
                        <a role="menuitem" href="{{ route('brand.material-matches.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Retours compatibles</span><span class="block text-xs text-slate-500">Trouver les textiles disponibles</span></a>
                    </x-brand-nav-dropdown>

                    @include('modules.partials.brand-nav')

                    <x-brand-nav-dropdown label="Marque" :active="request()->routeIs('brand.profile.*','brand.team.*')"><a role="menuitem" href="{{ route('brand.profile.edit') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Profil</span><span class="block text-xs text-slate-500">Identité et engagements</span></a><a role="menuitem" href="{{ route('brand.team.index') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-50"><span class="block text-sm font-bold">Équipe et départements</span><span class="block text-xs text-slate-500">Inviter et affecter les collaborateurs</span></a></x-brand-nav-dropdown>
                </nav>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <x-module.notification-bell />
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-700">Déconnexion</button>
                </form>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold lg:hidden"
                    @click="navigationOpen = ! navigationOpen"
                    :aria-expanded="navigationOpen.toString()"
                    aria-controls="brand-navigation"
                >
                    Menu
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75Zm0 4A.75.75 0 0 1 3.75 9h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 9.75Zm.75 3.25a.75.75 0 0 0 0 1.5h12.5a.75.75 0 0 0 0-1.5H3.75Z" clip-rule="evenodd" /></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="order-4 w-full lg:hidden" x-cloak x-show="navigationOpen">
                @csrf
                <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Déconnexion</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10">
        @if(session('success'))<div data-flash-message data-flash-type="success" class="hidden">{{ session('success') }}</div>@endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{ $slot }}
    </main>
    <x-brand-assistant />
</body>
</html>

