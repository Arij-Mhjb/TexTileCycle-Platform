{{-- En-tête clair propre à la « Seconde vie de la matière » (pas de bandeau sombre).
     Variables : $eyebrow, $title, $subtitle (optionnel), $chips (optionnel, [[valeur, libellé]]), slot $actions via $actionsHtml --}}
@once
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,500..900&display=swap">
<style>.mod-display { font-family: 'Archivo', ui-sans-serif, system-ui, sans-serif; font-stretch: 118%; letter-spacing: -.025em; }</style>
@endonce
<header class="relative mb-6 overflow-hidden rounded-3xl border border-teal-100 bg-white p-6 dark:border-teal-900/60 dark:bg-slate-900 sm:p-8">
    <div class="pointer-events-none absolute inset-0 opacity-60 dark:opacity-20" aria-hidden="true"
         style="background-image: radial-gradient(circle at 1px 1px, rgba(13,148,136,.18) 1px, transparent 0); background-size: 18px 18px; mask-image: linear-gradient(90deg, transparent, #000 70%);"></div>
    <div class="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full border-[18px] border-lime-300/40 dark:border-lime-500/20" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-2 top-10 h-28 w-28 rounded-full border-[10px] border-teal-400/30" aria-hidden="true"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-6">
        <div class="max-w-2xl">
            <p class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-800 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-200 dark:ring-teal-800">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M20 12a8 8 0 0 1-13.7 5.6M4 12a8 8 0 0 1 13.7-5.6"/><path d="M18 3v4h-4M6 21v-4h4"/></svg>
                {{ $eyebrow }}
            </p>
            <h1 class="mod-display mt-3 text-3xl font-extrabold leading-tight text-slate-900 dark:text-white sm:text-4xl">{{ $title }}</h1>
            @if(! empty($subtitle))<p class="mt-2 text-[15px] leading-7 text-slate-600 dark:text-slate-300">{{ $subtitle }}</p>@endif
        </div>
        @if(! empty($actionsHtml))<div class="flex flex-wrap gap-2">{!! $actionsHtml !!}</div>@endif
    </div>
    @if(! empty($chips))
        <dl class="relative mt-6 flex flex-wrap gap-3">
            @foreach($chips as [$value, $label])
                <div class="rounded-2xl bg-gradient-to-br from-teal-700 to-teal-900 px-4 py-3 text-white shadow-sm">
                    <dd class="mod-display text-xl font-extrabold tabular-nums">{{ $value }}</dd>
                    <dt class="text-xs font-semibold text-lime-200">{{ $label }}</dt>
                </div>
            @endforeach
        </dl>
    @endif
</header>
