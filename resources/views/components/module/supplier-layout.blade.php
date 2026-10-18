@props(['title' => 'Espace fournisseur'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <div class="flex items-center gap-4"><x-application-logo /><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">Espace fournisseur</span></div>
            @if(auth()->user()?->role === 'supplier')
                <nav class="flex items-center gap-2 text-sm font-bold">
                    <a href="{{ route('supplier.dashboard') }}" class="rounded-xl px-3 py-2 {{ request()->routeIs('supplier.dashboard', 'supplier.orders.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">Mes commandes</a>
                    <a href="{{ route('supplier.password.edit') }}" class="rounded-xl px-3 py-2 {{ request()->routeIs('supplier.password.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">Mot de passe</a>
                    <span class="hidden px-2 text-xs font-semibold text-slate-400 sm:inline">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-xl bg-slate-900 px-4 py-2 text-white">Déconnexion</button></form>
                </nav>
            @endif
        </div>
    </header>
    <main class="mx-auto max-w-5xl px-6 py-8">
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800"><span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-600 text-white">✓</span>{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
