@props(['module' => 'suppliers', 'eyebrow' => null, 'title', 'description' => null])
@php
$theme = [
    'suppliers' => ['base' => '#1d0b24', 'glow' => 'rgba(219,39,119,.32)', 'accent' => 'text-pink-300', 'rule' => 'bg-pink-400', 'text' => 'text-purple-100/80', 'label' => 'Fournisseurs & traçabilité'],
    'esg' => ['base' => '#06192a', 'glow' => 'rgba(34,211,238,.26)', 'accent' => 'text-cyan-300', 'rule' => 'bg-cyan-400', 'text' => 'text-sky-100/80', 'label' => 'Impact ESG'],
    'repair' => ['base' => '#1e1107', 'glow' => 'rgba(245,158,11,.30)', 'accent' => 'text-amber-300', 'rule' => 'bg-amber-400', 'text' => 'text-amber-50/80', 'label' => 'Seconde vie'],
][$module];
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
