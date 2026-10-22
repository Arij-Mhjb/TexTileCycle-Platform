<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Blog') · {{ config('app.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7faf8] text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-white">
    <header class="sticky inset-x-0 top-0 z-50 border-b border-slate-200/60 bg-white/90 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/85">
        <div class="mx-auto flex h-20 max-w-6xl items-center justify-between px-5 sm:px-6">
            <a href="{{ route('home') }}"><x-application-logo /></a>
            <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-600 dark:text-slate-300 md:flex">
                <a href="{{ route('home') }}" class="transition hover:text-emerald-600 dark:hover:text-emerald-300">Accueil</a>
                <a href="{{ route('blog.index') }}" class="transition hover:text-emerald-600 dark:hover:text-emerald-300">Blog</a>
            </nav>
            <div class="flex items-center gap-2">
                <x-theme-toggle />
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary">Mon espace</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-xl px-4 py-2.5 text-sm font-bold transition hover:bg-slate-900/5 dark:hover:bg-white/10 sm:inline-flex">Connexion</a>
                    <a href="{{ route('register') }}" class="btn-primary">Créer un compte</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-12 sm:px-6">
        @if(session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="border-t border-slate-200/60 py-10 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
        &copy; {{ now()->year }} {{ config('app.name') }} — Sensibilisation à la mode circulaire.
    </footer>
    <x-chatbot-widget />
</body>
</html>
