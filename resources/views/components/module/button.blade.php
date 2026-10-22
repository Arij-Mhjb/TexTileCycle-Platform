@props(['module' => 'suppliers', 'href' => null, 'variant' => 'solid'])
@php
$solid = [
    'suppliers' => 'bg-purple-700 text-white ring-1 ring-inset ring-purple-900/40 hover:bg-purple-800 focus-visible:ring-purple-500',
    'esg' => 'bg-sky-700 text-white ring-1 ring-inset ring-sky-900/40 hover:bg-sky-800 focus-visible:ring-sky-500',
    'loop' => 'bg-teal-700 text-white ring-1 ring-inset ring-teal-900/40 hover:bg-teal-800 focus-visible:ring-teal-500',
    'repair' => 'bg-amber-600 text-white ring-1 ring-inset ring-amber-800/40 hover:bg-amber-700 focus-visible:ring-amber-500',
][$module];
$soft = [
    'suppliers' => 'bg-white text-purple-800 ring-1 ring-inset ring-purple-200 hover:bg-purple-50',
    'esg' => 'bg-white text-sky-800 ring-1 ring-inset ring-sky-200 hover:bg-sky-50',
    'loop' => 'bg-white text-teal-800 ring-1 ring-inset ring-teal-200 hover:bg-teal-50',
    'repair' => 'bg-white text-amber-800 ring-1 ring-inset ring-amber-200 hover:bg-amber-50',
][$module];
$light = 'bg-white text-slate-900 hover:bg-white/90 focus-visible:ring-white';
$class = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-[inset_0_1px_0_rgba(255,255,255,.15),0_1px_2px_rgba(15,23,42,.12)] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
    .match ($variant) { 'soft' => $soft, 'light' => $light, default => $solid };
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $class, 'type' => 'submit']) }}>{{ $slot }}</button>
@endif
