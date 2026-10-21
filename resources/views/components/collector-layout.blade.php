@props(['title' => 'Espace collecteur'])
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} · {{ config('app.name') }}</title><x-theme-script />@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#f6f8f7] text-slate-800">
    <header class="border-b bg-white"><div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6"><a href="{{ route('collector.returns.index') }}" class="text-xl font-black text-emerald-700">TexTileCycle <span class="text-sm font-medium text-slate-500">Collecte</span></a><div class="flex items-center gap-4"><span class="text-sm font-bold">{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-black text-white">Déconnexion</button></form></div></div></header>
    <main class="mx-auto max-w-7xl px-6 py-10">@if(session('success'))<div data-flash-message data-flash-type="success" class="hidden">{{ session('success') }}</div>@endif @if($errors->any())<div class="mb-6 rounded-2xl bg-rose-50 p-4 text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif {{ $slot }}</main>
</body></html>
