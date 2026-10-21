@props(['module' => 'suppliers', 'links' => []])
@php
$active = ['suppliers' => 'border-purple-600 text-purple-900 dark:text-purple-200', 'esg' => 'border-sky-600 text-sky-900 dark:text-sky-200', 'repair' => 'border-amber-600 text-amber-900 dark:text-amber-200', 'loop' => 'border-teal-600 text-teal-900 dark:text-teal-200'][$module];
@endphp
<nav class="mb-8 flex gap-7 overflow-x-auto border-b border-slate-200 dark:border-slate-800" aria-label="Navigation du module">
    @foreach($links as [$routeName, $label, $pattern])
        @php $isActive = request()->routeIs($pattern); @endphp
        <a href="{{ route($routeName) }}" @if($isActive) aria-current="page" @endif
           class="-mb-px whitespace-nowrap border-b-2 pb-3 pt-1 text-[15px] font-semibold transition-colors {{ $isActive ? $active : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800 dark:hover:text-slate-200' }}">{{ $label }}</a>
    @endforeach
</nav>
