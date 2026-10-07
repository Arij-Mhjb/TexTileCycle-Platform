<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ config('app.name') }} · Marque</title>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('brand.dashboard') }}" class="text-xl font-black text-emerald-700">
                TexTileCycle <span class="text-sm font-medium text-slate-500">Brand Studio</span>
            </a>

            <nav class="flex flex-wrap gap-5 text-sm font-semibold">
                <a href="{{ route('brand.dashboard') }}">Pilotage</a>
                <a href="{{ route('brand.products.index') }}">Produits</a>
                <a href="{{ route('brand.products.import') }}">Import CSV</a>
                <a href="{{ route('brand.labels.index') }}">Mes labels</a>
                <a href="{{ route('brand.material-requests.index') }}">Matières</a>
                <a href="{{ route('brand.material-matches.index') }}">Retours compatibles</a>
                <a href="{{ route('brand.profile.edit') }}">Marque</a>
            </nav>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Déconnexion</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10">
        @if (session('success'))
            <div class="mb-6 rounded-xl bg-emerald-100 p-4 text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
