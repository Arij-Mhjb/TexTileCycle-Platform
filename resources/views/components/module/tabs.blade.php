@props(['module' => 'suppliers', 'links' => []])
@php
$active = ['suppliers' => 'bg-gradient-to-b from-purple-600 to-purple-700 text-white shadow-[0_4px_0_#581c87,0_10px_18px_-8px_rgba(126,34,206,.6)]', 'esg' => 'bg-gradient-to-b from-sky-500 to-sky-600 text-white shadow-[0_4px_0_#075985,0_10px_18px_-8px_rgba(2,132,199,.6)]', 'repair' => 'bg-gradient-to-b from-amber-500 to-amber-600 text-white shadow-[0_4px_0_#92400e,0_10px_18px_-8px_rgba(217,119,6,.6)]'][$module];
$idle = ['suppliers' => 'text-purple-800 hover:bg-purple-50 dark:text-purple-300 dark:hover:bg-purple-950/50', 'esg' => 'text-sky-700 hover:bg-sky-50 dark:text-sky-300 dark:hover:bg-sky-950/50', 'repair' => 'text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-950/50'][$module];
@endphp
<nav class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] mb-7 flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 dark:border-slate-800 dark:bg-slate-900" aria-label="Navigation du module">
    @foreach($links as [$routeName, $label, $pattern])
        @php $isActive = request()->routeIs($pattern); @endphp
        <a href="{{ route($routeName) }}" @if($isActive) aria-current="page" @endif
           class="whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-bold transition duration-200 {{ $isActive ? $active.' -translate-y-0.5' : $idle }}">{{ $label }}</a>
    @endforeach
</nav>
