@props(['onDark' => false])
<a {{ $attributes->merge(['class' => 'group inline-flex items-center gap-3']) }} href="{{ route('home') }}" aria-label="TexTileCycle — Accueil">
    <span class="relative grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white shadow-lg shadow-emerald-900/10 ring-1 ring-slate-200/70 transition duration-500 group-hover:-rotate-3 group-hover:scale-105 dark:ring-slate-700">
        <img src="{{ asset('images/brand/textilecycle-logo.png') }}" alt="" class="h-full w-full object-contain" width="48" height="48">
    </span>
    <span>
        <span @class(['block text-lg font-black tracking-tight dark:text-white', 'text-white' => $onDark, 'text-slate-900' => ! $onDark])>TexTile<span @class(['dark:text-emerald-400', 'text-emerald-300' => $onDark, 'text-emerald-600' => ! $onDark])>Cycle</span></span>
        <span @class(['block text-[9px] font-bold uppercase tracking-[.16em] dark:text-slate-500', 'text-slate-300' => $onDark, 'text-slate-400' => ! $onDark])>Circular textile platform</span>
    </span>
</a>
