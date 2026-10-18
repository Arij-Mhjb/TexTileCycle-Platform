@props(['module' => 'suppliers', 'eyebrow' => null, 'title', 'description' => null])
@php
$theme = [
    'suppliers' => ['bg' => 'from-[#2a0838] via-purple-800 to-pink-600', 'glow' => 'bg-pink-400/40', 'text' => 'text-purple-100', 'chip' => 'bg-white/10 text-pink-100 ring-white/20', 'label' => 'Fournisseurs & traçabilité', 'icon' => '🧵'],
    'esg' => ['bg' => 'from-sky-950 via-sky-800 to-cyan-600', 'glow' => 'bg-cyan-300/40', 'text' => 'text-sky-50', 'chip' => 'bg-white/10 text-sky-100 ring-white/20', 'label' => 'Impact ESG', 'icon' => '🌍'],
    'repair' => ['bg' => 'from-amber-950 via-orange-800 to-amber-500', 'glow' => 'bg-amber-300/40', 'text' => 'text-amber-50', 'chip' => 'bg-white/10 text-amber-100 ring-white/20', 'label' => 'Seconde vie', 'icon' => '♻️'],
][$module];
@endphp
@once
<style>
    .mod-scene { perspective: 900px; }
    .mod-scene__inner { transform: rotateX(var(--rx, 8deg)) rotateY(var(--ry, -14deg)); transform-style: preserve-3d; transition: transform .35s cubic-bezier(.2,.8,.2,1); }
    .mod-scene__shadow { transform: translateZ(-40px); }
    .mod-tile { box-shadow: 0 1px 0 rgb(255 255 255 / .25) inset, 0 6px 0 rgb(0 0 0 / .22), 0 18px 30px -12px rgb(0 0 0 / .45); }
    @media (prefers-reduced-motion: reduce) { .mod-scene__inner { transition: none; } }
</style>
@endonce
<section {{ $attributes->merge(['class' => 'relative mb-8 overflow-hidden rounded-[2rem] bg-gradient-to-br p-7 text-white shadow-2xl sm:p-10 '.$theme['bg']]) }}>
    <div class="pointer-events-none absolute -right-10 top-1/2 h-72 w-72 -translate-y-1/2 rounded-full blur-3xl {{ $theme['glow'] }}"></div>
    <div class="pointer-events-none absolute inset-0 opacity-[.07]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;"></div>

    <div class="relative grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_21rem]">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold ring-1 backdrop-blur {{ $theme['chip'] }}"><span aria-hidden="true">{{ $theme['icon'] }}</span>{{ $eyebrow ?? $theme['label'] }}</span>
            <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-[2.6rem] sm:leading-[1.1]">{{ $title }}</h1>
            @if($description)<p class="mt-3 max-w-2xl text-[15px] leading-7 {{ $theme['text'] }}">{{ $description }}</p>@endif
            @isset($actions)<div class="mt-6 flex flex-wrap gap-3">{{ $actions }}</div>@endisset
        </div>

        {{-- Scène 3D isométrique du module : s'incline selon la position de la souris. --}}
        <div class="mod-scene hidden lg:block" aria-hidden="true"
             x-data
             @pointermove="if (!matchMedia('(prefers-reduced-motion: reduce)').matches) { const r = $el.getBoundingClientRect(); $refs.inner.style.setProperty('--ry', ((($event.clientX - r.left) / r.width) - .5) * 30 + 'deg'); $refs.inner.style.setProperty('--rx', (.5 - (($event.clientY - r.top) / r.height)) * 22 + 'deg'); }"
             @pointerleave="$refs.inner.style.removeProperty('--rx'); $refs.inner.style.removeProperty('--ry')">
            <div x-ref="inner" class="mod-scene__inner">
                @include('components.module.scenes.'.$module)
            </div>
        </div>
    </div>

    @isset($stats)<div class="relative mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{{ $stats }}</div>@endisset
</section>
