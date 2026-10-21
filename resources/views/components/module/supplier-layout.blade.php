@props(['title' => 'Espace fournisseur'])
@php
$user = auth()->user();
$isSupplier = $user?->role === 'supplier';
$suppliers = $isSupplier ? \App\Models\Supplier::with('brand')->where('user_id', $user->id)->get() : collect();
$ids = $suppliers->pluck('id');
// Compteurs « à faire », affichés dans le menu.
$counts = $isSupplier ? [
    'orders' => \App\Models\PurchaseOrder::whereIn('supplier_id', $ids)->whereIn('status', ['sent', 'ordered', 'accepted', 'partially_shipped'])->count(),
    'batches' => \App\Models\SupplierBatch::whereIn('supplier_id', $ids)->whereDoesntHave('certificates', fn ($query) => $query->where('status', '!=', 'rejected'))->count(),
    'recycling' => \App\Models\RecyclingLoop::whereIn('supplier_id', $ids)->whereIn('status', ['requested', 'in_transit', 'received', 'transformed'])->count(),
] : [];
$groups = [
    ['Mon activité', [
        ['supplier.dashboard', ['supplier.dashboard'], 'Accueil', 'M4 10l8-6 8 6v9a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1z', null],
        ['supplier.dashboard', ['supplier.orders.*'], 'Commandes', 'M6 3h9l4 4v14H6zM14 3v5h5M9 13h7M9 17h5', $counts['orders'] ?? null, '#commandes'],
    ]],
    ['Traçabilité', [
        ['supplier.batches.index', ['supplier.batches.*'], 'Lots & certificats', 'M12 3 4 6v6c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V6zM9 12l2 2 4-4', $counts['batches'] ?? null],
    ]],
    ['Seconde vie', [
        ['supplier.recycling.index', ['supplier.recycling.*'], 'Recyclage', 'M20 12a8 8 0 0 1-13.7 5.6M4 12a8 8 0 0 1 13.7-5.6M18 3v4h-4M6 21v-4h4', $counts['recycling'] ?? null],
    ]],
    ['Compte', [
        ['supplier.account.edit', ['supplier.account.*', 'supplier.password.*'], 'Mon compte', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0', null],
    ]],
];
$links = collect($groups)->flatMap(fn ($group) => $group[1]);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:focus-visible { outline-color: #c026d3; }</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    {{-- Menu latéral : même structure que l'espace admin, palette prune / fuchsia propre aux fournisseurs. --}}
    @if($isSupplier)
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 flex-col border-r border-[#3a1247] bg-[#1f0a29] lg:flex">
        <div class="flex h-20 items-center gap-3 border-b border-white/10 bg-white px-6">
            <x-application-logo />
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Espace fournisseur">
            @if($isSupplier)
                @foreach($groups as [$groupLabel, $items])
                    <p class="mb-1.5 mt-5 px-3 text-[10px] font-black uppercase tracking-[.18em] text-fuchsia-300/70 first:mt-0">{{ $groupLabel }}</p>
                    <div class="space-y-1">
                        @foreach($items as $item)
                            @php [$route, $patterns, $label, $icon, $count] = $item; $active = request()->routeIs(...$patterns); @endphp
                            <a href="{{ route($route).($item[5] ?? '') }}" @if($active) aria-current="page" @endif
                               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $active ? 'bg-white text-[#1f0a29] shadow-lg' : 'text-fuchsia-50/80 hover:bg-white/10 hover:text-white' }}">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $active ? 'bg-fuchsia-600 text-white' : 'bg-white/[.06] text-fuchsia-300 group-hover:text-fuchsia-200' }}">
                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                                </span>
                                <span class="flex-1">{{ $label }}</span>
                                @if($count)<span class="rounded-full px-2 py-0.5 text-[11px] font-black tabular-nums {{ $active ? 'bg-fuchsia-100 text-fuchsia-800' : 'bg-fuchsia-500 text-white' }}">{{ $count }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </nav>
        @if($isSupplier && $suppliers->isNotEmpty())
            <div class="border-t border-white/10 p-4">
                <div class="relative overflow-hidden rounded-2xl bg-[#2d0f3b] p-4 text-white ring-1 ring-white/10">
                    <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-fuchsia-400/30 blur-xl"></div>
                    <p class="relative text-xs font-semibold text-fuchsia-200">Vous travaillez pour</p>
                    <p class="relative mt-1 text-sm font-bold">{{ $suppliers->pluck('brand.name')->filter()->unique()->join(', ') ?: '—' }}</p>
                    <p class="relative mt-1 text-xs leading-5 text-fuchsia-100/60">Fiche fournisseur : {{ $suppliers->pluck('name')->unique()->join(', ') }}</p>
                </div>
            </div>
        @endif
    </aside>
    @endif

    <div class="flex min-h-screen flex-col {{ $isSupplier ? 'lg:pl-72' : '' }}">
        <header class="sticky top-0 z-30 border-b border-fuchsia-200/70 bg-fuchsia-50/95 backdrop-blur-xl">
            <div class="flex h-20 items-center justify-between gap-4 px-5 sm:px-8">
                <div class="min-w-0">
                    @unless($isSupplier)<div class="mb-1"><x-application-logo /></div>@endunless
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold text-slate-400">{{ now()->locale('fr')->translatedFormat('l d F Y') }}</p>
                        <span class="rounded-full bg-fuchsia-700 px-2.5 py-1 text-[9px] font-black uppercase tracking-[.16em] text-white">Fournisseur</span>
                    </div>
                    <h1 class="mt-1 truncate text-lg font-black tracking-tight text-[#2d0f3b]">{{ $title }}</h1>
                </div>
                @if($isSupplier)
                    <div class="flex items-center gap-3">
                        <x-module.notification-bell />
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="flex items-center gap-3 rounded-xl border border-fuchsia-200 bg-white p-1.5 pr-3">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-fuchsia-700 text-sm font-black text-white">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                <span class="hidden text-left sm:block"><span class="block text-sm font-bold text-slate-800">{{ $user->name }}</span><span class="block text-xs text-fuchsia-600">Fournisseur</span></span>
                            </button>
                            <div x-cloak x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                                <p class="truncate px-3 py-2 text-xs text-slate-400">{{ $user->email }}</p>
                                <a href="{{ route('supplier.account.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-slate-50">Mon compte</a>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50">Déconnexion</button></form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @if($isSupplier)
                <nav class="flex gap-2 overflow-x-auto border-t border-fuchsia-100 px-5 py-2 lg:hidden" aria-label="Espace fournisseur">
                    @foreach($links as $item)
                        @php $active = request()->routeIs(...$item[1]); @endphp
                        <a href="{{ route($item[0]).($item[5] ?? '') }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-bold {{ $active ? 'bg-fuchsia-100 text-fuchsia-800' : 'text-slate-500 hover:bg-white' }}">{{ $item[2] }}@if($item[4]) <span class="ml-1 rounded-full bg-fuchsia-600 px-1.5 text-[10px] text-white">{{ $item[4] }}</span>@endif</a>
                    @endforeach
                </nav>
            @endif
        </header>

        <main class="flex-1 px-5 py-7 sm:px-8">
            <div class="mx-auto max-w-6xl">
                @if(session('success'))<div data-flash-message data-flash-type="success" class="hidden">{{ session('success') }}</div>@endif
                @if(session('warning'))
                    <div role="status" class="mb-6 flex gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-amber-500 text-xs font-bold text-white">!</span><p>{{ session('warning') }}</p></div>
                @endif
                @if($errors->any())
                    <div role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><p class="font-bold">Veuillez corriger les champs signalés.</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                {{ $slot }}
            </div>
        </main>
        <footer class="border-t border-slate-200 px-5 py-5 text-xs text-slate-400 sm:px-8">© {{ now()->year }} {{ config('app.name') }} · Espace fournisseur</footer>
    </div>
</body>
</html>
