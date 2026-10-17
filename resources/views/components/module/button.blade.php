@props(['module' => 'suppliers', 'href' => null, 'variant' => 'solid'])
@php
$solid = ['suppliers' => 'bg-indigo-600 text-white hover:bg-indigo-500 focus:ring-indigo-500', 'esg' => 'bg-sky-600 text-white hover:bg-sky-500 focus:ring-sky-500', 'repair' => 'bg-amber-600 text-white hover:bg-amber-500 focus:ring-amber-500'][$module];
$soft = ['suppliers' => 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100', 'esg' => 'bg-sky-50 text-sky-700 hover:bg-sky-100', 'repair' => 'bg-amber-50 text-amber-800 hover:bg-amber-100'][$module];
$class = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold shadow-sm transition hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 '.($variant === 'soft' ? $soft : ($variant === 'light' ? 'bg-white text-slate-900 hover:bg-slate-100 focus:ring-white' : $solid));
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $class, 'type' => 'submit']) }}>{{ $slot }}</button>
@endif
