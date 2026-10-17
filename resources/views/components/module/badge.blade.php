@props(['tone' => 'neutral'])
@php
$classes = [
    'good' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300',
    'warn' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300',
    'bad' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/60 dark:text-rose-300',
    'neutral' => 'bg-slate-100 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-300',
    'suppliers' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-950/60 dark:text-indigo-300',
    'esg' => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-950/60 dark:text-sky-300',
    'repair' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-950/60 dark:text-amber-300',
][$tone] ?? 'bg-slate-100 text-slate-600';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset '.$classes]) }}>{{ $slot }}</span>
