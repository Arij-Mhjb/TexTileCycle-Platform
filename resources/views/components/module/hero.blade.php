@props(['module' => 'suppliers', 'eyebrow' => null, 'title', 'description' => null])
@php
$theme = [
    'suppliers' => ['base' => '#1d0b24', 'glow' => 'rgba(219,39,119,.32)', 'accent' => 'text-pink-300', 'rule' => 'bg-pink-400', 'text' => 'text-purple-100/80', 'label' => 'Fournisseurs & traçabilité'],
    'esg' => ['base' => '#06192a', 'glow' => 'rgba(34,211,238,.26)', 'accent' => 'text-cyan-300', 'rule' => 'bg-cyan-400', 'text' => 'text-sky-100/80', 'label' => 'Impact ESG'],
    'loop' => ['base' => '#04201d', 'glow' => 'rgba(45,212,191,.26)', 'accent' => 'text-teal-300', 'rule' => 'bg-teal-400', 'text' => 'text-teal-50/80', 'label' => 'Recyclage fournisseurs'],
    'repair' => ['base' => '#1e1107', 'glow' => 'rgba(245,158,11,.30)', 'accent' => 'text-amber-300', 'rule' => 'bg-amber-400', 'text' => 'text-amber-50/80', 'label' => 'Seconde vie'],
][$module];
// Style « centre de pilotage » (comme l'admin) pour Fournisseurs, Impact ESG et Recyclage ; la Seconde vie garde son en-tête tissé.
$console = [
    'suppliers' => ['glow' => 'bg-fuchsia-500/25', 'glow2' => 'bg-purple-500/20', 'accent' => 'text-fuchsia-300', 'dot' => 'bg-fuchsia-400'],
    'esg' => ['glow' => 'bg-sky-500/25', 'glow2' => 'bg-cyan-400/15', 'accent' => 'text-sky-300', 'dot' => 'bg-sky-400'],
    'loop' => ['glow' => 'bg-teal-500/25', 'glow2' => 'bg-emerald-400/15', 'accent' => 'text-teal-300', 'dot' => 'bg-teal-400'],
][$module] ?? null;
@endphp
@once
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,500..900&display=swap">
<style>
    .mod-display { font-family: 'Archivo', ui-sans-serif, system-ui, sans-serif; font-stretch: 118%; letter-spacing: -.025em; }
    /* Armure sergé : la texture du tissu, très discrète */
    .mod-weave { background-image: repeating-linear-gradient(45deg, rgba(255,255,255,.035) 0 1px, transparent 1px 5px), repeating-linear-gradient(-45deg, rgba(0,0,0,.18) 0 1px, transparent 1px 5px); }
    .mod-thread { stroke-dasharray: 1400; animation: mod-weave-in 2.2s cubic-bezier(.65,0,.35,1) both; }
    .mod-thread:nth-child(2) { animation-delay: .15s } .mod-thread:nth-child(3) { animation-delay: .3s } .mod-thread:nth-child(4) { animation-delay: .45s } .mod-thread:nth-child(5) { animation-delay: .6s }
    @keyframes mod-weave-in { from { stroke-dashoffset: 1400; } to { stroke-dashoffset: 0; } }
    @media (prefers-reduced-motion: reduce) { .mod-thread { animation: none; } }
</style>
@endonce
@if($console)
<section {{ $attributes->merge(['class' => 'relative isolate mb-8 overflow-hidden rounded-[2rem] bg-slate-950 p-7 text-white shadow-2xl shadow-slate-900/15 sm:p-9']) }}>
    <div class="pointer-events-none absolute -right-28 -top-36 -z-10 h-96 w-96 rounded-full {{ $console['glow'] }} blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 -z-10 h-40 w-72 rounded-full {{ $console['glow2'] }} blur-3xl"></div>
    <div class="relative flex flex-col gap-7 xl:flex-row xl:items-end xl:justify-between">
        <div class="min-w-0 max-w-2xl xl:flex-1">
            <p class="flex items-center gap-2 text-xs font-black uppercase tracking-[.2em] {{ $console['accent'] }}"><span class="h-2 w-2 animate-pulse rounded-full {{ $console['dot'] }}"></span>{{ $eyebrow ?? $theme['label'] }}</p>
            <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">{{ $title }}</h1>
            @if($description)<p class="mt-3 max-w-2xl leading-7 text-slate-300">{{ $description }}</p>@endif
            @isset($actions)<div class="mt-6 flex flex-wrap gap-3">{{ $actions }}</div>@endisset
        </div>
        @isset($stats)
            <div class="grid shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 xl:w-[30rem] xl:grid-cols-2">{{ $stats }}</div>
        @endisset
    </div>
</section>
@else
<section {{ $attributes->merge(['class' => 'relative isolate mb-8 overflow-hidden rounded-2xl text-white shadow-[0_24px_60px_-28px_rgba(0,0,0,.6)]']) }} style="background-color: {{ $theme['base'] }}">
    <div class="mod-weave absolute inset-0 -z-10"></div>
    <div class="absolute inset-0 -z-10" style="background: radial-gradient(60% 90% at 88% 10%, {{ $theme['glow'] }}, transparent 70%)"></div>
    <div class="relative px-7 pb-9 pt-9 sm:px-10 sm:pt-11">
        <div class="pointer-events-none absolute inset-y-0 right-0 -z-10 hidden w-[48%] md:block" aria-hidden="true">
            @include('components.module.threads.'.$module)
        </div>
        <div class="flex items-center gap-3">
            <span class="h-px w-8 {{ $theme['rule'] }}"></span>
            <p class="text-sm font-semibold {{ $theme['accent'] }}">{{ $eyebrow ?? $theme['label'] }}</p>
        </div>
        <div class="mt-4 max-w-2xl">
            <h1 class="mod-display text-[2rem] font-extrabold leading-[1.05] sm:text-5xl">{{ $title }}</h1>
            @if($description)<p class="mt-4 max-w-xl text-[15px] leading-7 {{ $theme['text'] }}">{{ $description }}</p>@endif
            @isset($actions)<div class="mt-7 flex flex-wrap gap-3">{{ $actions }}</div>@endisset
        </div>
    </div>

    @isset($stats)
        {{-- Registre des chiffres clés : une ligne, des séparateurs fins, pas de cartes. --}}
        <div class="grid grid-cols-2 border-t border-white/10 bg-black/20 backdrop-blur-sm lg:grid-cols-4 [&>*]:border-white/10 [&>*:nth-child(odd)]:border-r lg:[&>*]:border-r lg:[&>*:last-child]:border-r-0 [&>*:nth-child(-n+2)]:border-b lg:[&>*]:border-b-0">{{ $stats }}</div>
    @endisset
</section>
@endif
