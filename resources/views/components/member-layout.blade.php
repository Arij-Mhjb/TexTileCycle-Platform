<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Espace collaborateur' }} · {{ config('app.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
    <header class="border-b border-indigo-100 bg-white/95 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-4">
                <a href="{{ route('member.tasks.index') }}" class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-indigo-600 text-lg font-black text-white shadow-lg shadow-indigo-200 dark:shadow-none">TC</a>
                <div class="min-w-0"><p class="truncate text-sm font-black">Espace collaborateur</p><p class="truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ auth()->user()->brandMembership?->brand?->name }}</p></div>
            </div>
            <div class="flex items-center gap-2"><x-theme-toggle /><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300">Déconnexion</button></form></div>
        </div>
    </header>
    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{{ $slot }}</main>
</body>
</html>
