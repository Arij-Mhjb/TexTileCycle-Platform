{{-- En-tête de la boucle de recyclage : clair, une seule couleur (sarcelle).
     Variables : $eyebrow, $title, $subtitle?, $actionsHtml?, $chips? ([[valeur, libellé]]) --}}
@once
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,500..900&display=swap">
<style>
    .rcy-display { font-family: 'Archivo', ui-sans-serif, system-ui, sans-serif; font-stretch: 115%; letter-spacing: -.02em; }
    .rcy-dots { background-image: radial-gradient(circle at 1px 1px, rgba(13,148,136,.16) 1px, transparent 0); background-size: 18px 18px; }
    @keyframes rcy-spin { to { transform: rotate(360deg); } }
    .rcy-orbit { animation: rcy-spin 40s linear infinite; transform-origin: center; }
    @media (prefers-reduced-motion: reduce) { .rcy-orbit { animation: none; } }
</style>
@endonce
<header class="relative mb-6 overflow-hidden rounded-3xl border border-teal-100 bg-white dark:border-teal-900/60 dark:bg-slate-900">
    <div class="rcy-dots pointer-events-none absolute inset-0 opacity-70 dark:opacity-25" style="mask-image: linear-gradient(90deg, transparent 20%, #000 80%)" aria-hidden="true"></div>
    <svg class="rcy-orbit pointer-events-none absolute -right-16 -top-20 hidden h-72 w-72 text-teal-500/25 md:block" viewBox="0 0 200 200" fill="none" aria-hidden="true">
        <circle cx="100" cy="100" r="80" stroke="currentColor" stroke-width="14" stroke-dasharray="120 40" stroke-linecap="round"/>
    </svg>
    <div class="relative flex flex-wrap items-end justify-between gap-6 p-6 sm:p-8">
        <div class="max-w-2xl">
            <p class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-800 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-200 dark:ring-teal-800">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M20 12a8 8 0 0 1-13.7 5.6M4 12a8 8 0 0 1 13.7-5.6"/><path d="M18 3v4h-4M6 21v-4h4"/></svg>
                {{ $eyebrow }}
            </p>
            <h1 class="rcy-display mt-3 text-3xl font-extrabold leading-tight text-slate-900 dark:text-white sm:text-[2.6rem]">{{ $title }}</h1>
            @if(! empty($subtitle))<p class="mt-2 text-[15px] leading-7 text-slate-600 dark:text-slate-300">{{ $subtitle }}</p>@endif
        </div>
        @if(! empty($actionsHtml))<div class="flex flex-wrap gap-2">{!! $actionsHtml !!}</div>@endif
    </div>
    @if(! empty($chips))
        <dl class="relative grid grid-cols-2 border-t border-teal-100 bg-teal-50/40 dark:border-teal-900/60 dark:bg-teal-950/20 lg:grid-cols-4">
            @foreach($chips as [$value, $label])
                <div class="border-teal-100 px-6 py-4 dark:border-teal-900/60 sm:px-8 [&:not(:last-child)]:border-r">
                    <dd class="rcy-display text-2xl font-extrabold tabular-nums text-teal-800 dark:text-teal-200">{{ $value }}</dd>
                    <dt class="mt-0.5 text-xs font-semibold text-slate-500">{{ $label }}</dt>
                </div>
            @endforeach
        </dl>
    @endif
</header>
