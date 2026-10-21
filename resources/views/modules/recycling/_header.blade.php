{{-- En-tête des pages Recyclage, au style « centre de pilotage » de l'admin (palette sarcelle).
     Variables : $eyebrow, $title, $subtitle?, $actionsHtml?, $chips? ([[valeur, libellé]]) --}}
@once
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,500..900&display=swap">
<style>
    .rcy-display { font-family: 'Archivo', ui-sans-serif, system-ui, sans-serif; font-stretch: 115%; letter-spacing: -.02em; }
</style>
@endonce
<header class="relative isolate mb-6 overflow-hidden rounded-[2rem] bg-slate-950 p-7 text-white shadow-2xl shadow-slate-900/15 sm:p-9">
    <div class="pointer-events-none absolute -right-28 -top-36 -z-10 h-96 w-96 rounded-full bg-teal-500/25 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 -z-10 h-40 w-72 rounded-full bg-emerald-400/15 blur-3xl" aria-hidden="true"></div>
    <div class="relative flex flex-col gap-7 xl:flex-row xl:items-end xl:justify-between">
        <div class="min-w-0 max-w-2xl xl:flex-1">
            <p class="flex items-center gap-2 text-xs font-black uppercase tracking-[.2em] text-teal-300"><span class="h-2 w-2 animate-pulse rounded-full bg-teal-400"></span>{{ $eyebrow }}</p>
            <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">{{ $title }}</h1>
            @if(! empty($subtitle))<p class="mt-3 leading-7 text-slate-300">{{ $subtitle }}</p>@endif
            @if(! empty($actionsHtml))<div class="mt-6 flex flex-wrap gap-2">{!! $actionsHtml !!}</div>@endif
        </div>
        @if(! empty($chips))
            <dl class="grid shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 xl:w-[30rem] xl:grid-cols-2">
                @foreach($chips as [$value, $label])
                    <div class="min-w-[9rem] rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</dt>
                        <dd class="mt-2 text-xl font-black tabular-nums text-teal-200">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</header>
