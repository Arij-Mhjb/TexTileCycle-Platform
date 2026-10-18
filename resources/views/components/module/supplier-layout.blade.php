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
<body class="min-h-screen bg-[#faf7fc] text-slate-800">
    {{-- Bandeau violet-rose propre au module Fournisseurs --}}
    <header class="relative overflow-hidden bg-gradient-to-r from-[#2a0838] via-purple-800 to-pink-600 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[.08]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>
        <div class="relative mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <a href="{{ auth()->user()?->role === 'supplier' ? route('supplier.dashboard') : url('/') }}" class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15 text-xl shadow-[0_3px_0_rgba(0,0,0,.25)] ring-1 ring-white/25" aria-hidden="true">🧵</span>
                <span class="leading-tight"><span class="block text-base font-black">{{ config('app.name') }}</span><span class="block text-xs font-semibold text-pink-100">Espace fournisseur</span></span>
            </a>
            @if(auth()->user()?->role === 'supplier')
                <nav class="flex flex-wrap items-center gap-1.5 text-sm font-bold" aria-label="Espace fournisseur">
                    <a href="{{ route('supplier.dashboard') }}" class="rounded-xl px-3 py-2 transition {{ request()->routeIs('supplier.dashboard', 'supplier.orders.*') ? 'bg-white text-purple-800 shadow-[0_3px_0_rgba(0,0,0,.2)]' : 'text-white/85 hover:bg-white/10' }}">Mes commandes</a>
                    <a href="{{ route('supplier.password.edit') }}" class="rounded-xl px-3 py-2 transition {{ request()->routeIs('supplier.password.*') ? 'bg-white text-purple-800 shadow-[0_3px_0_rgba(0,0,0,.2)]' : 'text-white/85 hover:bg-white/10' }}">Mot de passe</a>
                    <span class="hidden px-2 text-xs font-semibold text-pink-100/80 md:inline">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-xl bg-black/25 px-4 py-2 text-white ring-1 ring-white/20 transition hover:bg-black/35">Déconnexion</button></form>
                </nav>
            @endif
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-8">
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-[0_3px_0_#a7f3d0]"><span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-600 text-white">✓</span>{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
