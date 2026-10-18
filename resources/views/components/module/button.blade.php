@props(['module' => 'suppliers', 'href' => null, 'variant' => 'solid'])
@php
// Boutons « en relief » : un bord inférieur épais donne l'effet 3D, qui s'enfonce au clic.
$solid = [
    'suppliers' => 'bg-gradient-to-b from-purple-600 to-purple-700 text-white shadow-[0_4px_0_#581c87] hover:from-purple-500 hover:to-purple-600 focus-visible:ring-purple-400',
    'esg' => 'bg-gradient-to-b from-sky-500 to-sky-600 text-white shadow-[0_4px_0_#075985] hover:from-sky-400 hover:to-sky-500 focus-visible:ring-sky-400',
    'repair' => 'bg-gradient-to-b from-amber-500 to-amber-600 text-white shadow-[0_4px_0_#92400e] hover:from-amber-400 hover:to-amber-500 focus-visible:ring-amber-400',
][$module];
$soft = [
    'suppliers' => 'bg-purple-50 text-purple-800 shadow-[0_3px_0_#e9d5ff] hover:bg-purple-100',
    'esg' => 'bg-sky-50 text-sky-700 shadow-[0_3px_0_#bae6fd] hover:bg-sky-100',
    'repair' => 'bg-amber-50 text-amber-800 shadow-[0_3px_0_#fde68a] hover:bg-amber-100',
][$module];
$light = 'bg-white text-slate-900 shadow-[0_4px_0_rgba(15,23,42,.25)] hover:bg-slate-50 focus-visible:ring-white';
$class = 'inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition duration-150 hover:-translate-y-0.5 active:translate-y-[3px] active:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 '
    .match ($variant) { 'soft' => $soft, 'light' => $light, default => $solid };
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $class, 'type' => 'submit']) }}>{{ $slot }}</button>
@endif
