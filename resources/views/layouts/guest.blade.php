<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 transition-colors duration-300 dark:bg-slate-950">
    <div class="grid min-h-screen lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-emerald-700 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <img src="{{ asset('images/story/clothes-sorting.jpg') }}" alt="Tri de vêtements pour leur donner une seconde vie" class="absolute inset-0 h-full w-full object-cover opacity-25" fetchpriority="high">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-950/95 via-emerald-800/85 to-slate-950/80"></div>
            <div class="relative"><x-application-logo :on-dark="true" /></div>
            <div class="relative max-w-lg animate-fade-up"><p class="text-sm font-bold uppercase tracking-[.2em] text-emerald-200">Trace · Return · Reuse · Recycle</p><h1 class="mt-5 text-5xl font-black leading-tight tracking-tight">Chaque textile mérite une seconde vie.</h1><p class="mt-5 text-lg leading-8 text-emerald-50">Identifiez les matières, trouvez le bon partenaire et mesurez l’impact de chaque retour.</p></div>
            <p class="relative text-sm text-emerald-100">Projet Applications Web Avancées · ESPRIT 2026–2027</p>
        </section>
        <main class="relative flex items-center justify-center p-6 sm:p-10">
            <div class="absolute right-6 top-6"><x-theme-toggle /></div>
            <div class="w-full max-w-md rounded-3xl border border-white bg-white p-7 shadow-2xl transition-colors dark:border-slate-800 dark:bg-slate-900 sm:p-10"><div class="mb-8 lg:hidden"><x-application-logo /></div>{{ $slot }}</div>
        </main>
    </div>
</body>
</html>
