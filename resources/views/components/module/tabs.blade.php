@props(['module' => 'suppliers', 'links' => []])
@php
$active = ['suppliers' => 'bg-indigo-600 text-white shadow-indigo-900/20', 'esg' => 'bg-sky-600 text-white shadow-sky-900/20', 'repair' => 'bg-amber-600 text-white shadow-amber-900/20'][$module];
$idle = ['suppliers' => 'text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/50', 'esg' => 'text-sky-700 hover:bg-sky-50 dark:text-sky-300 dark:hover:bg-sky-950/50', 'repair' => 'text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-950/50'][$module];
@endphp
<nav class="mb-6 flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-1.5 dark:border-slate-800 dark:bg-slate-900" aria-label="Navigation du module">
    @foreach($links as [$routeName, $label, $pattern])
        <a href="{{ route($routeName) }}" class="whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold transition {{ request()->routeIs($pattern) ? $active.' shadow' : $idle }}">{{ $label }}</a>
    @endforeach
</nav>
