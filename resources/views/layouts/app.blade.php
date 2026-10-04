<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Gestion' }} · {{ config('app.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 transition-colors duration-300 dark:bg-slate-950">
    <x-layout.sidebar />
    <div class="flex min-h-screen flex-col lg:pl-72">
        <x-layout.header :title="$title ?? null" />
        <main class="flex-1 px-5 py-7 sm:px-8">
            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200"><span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-600 text-white">✓</span>{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200"><p class="font-bold">Veuillez corriger les champs signalés.</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            {{ $slot }}
        </main>
        <x-layout.footer />
    </div>
</body>
</html>
