@props(['module' => 'suppliers', 'eyebrow' => null, 'title', 'description' => null])
@php
$theme = [
    'suppliers' => ['bg' => 'from-indigo-950 via-indigo-800 to-violet-600', 'eyebrow' => 'text-indigo-200', 'text' => 'text-indigo-50', 'label' => 'Fournisseurs & traçabilité'],
    'esg' => ['bg' => 'from-sky-950 via-sky-800 to-cyan-600', 'eyebrow' => 'text-sky-200', 'text' => 'text-sky-50', 'label' => 'Impact ESG'],
    'repair' => ['bg' => 'from-amber-950 via-orange-800 to-amber-500', 'eyebrow' => 'text-amber-200', 'text' => 'text-amber-50', 'label' => 'Seconde vie'],
][$module];
@endphp
<section {{ $attributes->merge(['class' => 'relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br p-7 text-white shadow-xl sm:p-9 '.$theme['bg']]) }}>
    <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-6">
        <div class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[.24em] {{ $theme['eyebrow'] }}">{{ $eyebrow ?? $theme['label'] }}</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ $title }}</h1>
            @if($description)<p class="mt-3 max-w-2xl text-sm leading-6 {{ $theme['text'] }}">{{ $description }}</p>@endif
        </div>
        @isset($actions)<div class="flex flex-wrap gap-3">{{ $actions }}</div>@endisset
    </div>
    @isset($stats)<div class="relative mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">{{ $stats }}</div>@endisset
</section>
