@props(['title' => 'Espace fournisseur'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:focus-visible { outline-color: #a855f7; }</style>
</head>
<body class="min-h-screen bg-[#f8f7f9] text-slate-800">
    {{-- En-tête de l'espace fournisseur : tissu aubergine, couleur propre au module Fournisseurs --}}
    <header class="relative text-white" style="background-color:#1d0b24; background-image: repeating-linear-gradient(45deg, rgba(255,255,255,.035) 0 1px, transparent 1px 5px)">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <a href="{{ auth()->user()?->role === 'supplier' ? route('supplier.dashboard') : url('/') }}" class="flex items-center gap-3">
                <span class="grid h-9 w-9 grid-cols-3 gap-[3px] rounded-md bg-pink-400/15 p-[7px] ring-1 ring-pink-300/30" aria-hidden="true">
                    @foreach(range(1, 9) as $i)<span class="rounded-[1px] {{ $i % 2 ? 'bg-pink-300' : 'bg-purple-400/60' }}"></span>@endforeach
                </span>
                <span class="leading-tight"><span class="block text-[15px] font-bold">{{ config('app.name') }}</span><span class="block text-xs text-pink-200/80">Espace fournisseur</span></span>
            </a>
            @if(auth()->user()?->role === 'supplier')
                <nav class="flex flex-wrap items-center gap-1 text-sm font-semibold" aria-label="Espace fournisseur">
                    @foreach([
                        ['supplier.dashboard', ['supplier.dashboard', 'supplier.orders.*'], 'Accueil', 'M4 10l8-6 8 6v9a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1z'],
                        ['supplier.batches.index', ['supplier.batches.*'], 'Lots & certificats', 'M12 3 4 6v6c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V6zM9 12l2 2 4-4'],
                        ['supplier.recycling.index', ['supplier.recycling.*'], 'Recyclage', 'M20 12a8 8 0 0 1-13.7 5.6M4 12a8 8 0 0 1 13.7-5.6M18 3v4h-4M6 21v-4h4'],
                        ['supplier.account.edit', ['supplier.account.*'], 'Mon compte', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0'],
                    ] as [$route, $patterns, $label, $icon])
                        <a href="{{ route($route) }}" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 transition-colors {{ request()->routeIs(...$patterns) ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white' }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>{{ $label }}
                        </a>
                    @endforeach
                    <span class="mx-2 hidden h-5 w-px bg-white/15 md:block"></span>
                    <x-module.notification-bell dark />
                    <span class="hidden pr-2 text-xs text-white/50 md:inline">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-md px-3 py-2 text-white/70 ring-1 ring-white/15 transition-colors hover:bg-white/10 hover:text-white">Déconnexion</button></form>
                </nav>
            @endif
        </div>
        <div class="h-[3px] bg-gradient-to-r from-purple-500 via-pink-400 to-purple-500"></div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-8">
        @if(session('success'))<div data-flash-message data-flash-type="success" class="hidden">{{ session('success') }}</div>@endif
        @if(session('warning'))
            <div role="status" class="mb-6 flex gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-amber-500 text-xs font-bold text-white">!</span><p>{{ session('warning') }}</p></div>
        @endif
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
