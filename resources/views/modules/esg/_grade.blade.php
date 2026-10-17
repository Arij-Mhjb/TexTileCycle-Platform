{{-- Pastille de classe environnementale A→E : $report, $size (optionnel) --}}
@php
$gradeClasses = [
    'A' => 'bg-emerald-500 text-white', 'B' => 'bg-lime-500 text-white', 'C' => 'bg-yellow-400 text-slate-900',
    'D' => 'bg-orange-500 text-white', 'E' => 'bg-rose-600 text-white',
][$report->grade] ?? 'bg-slate-400 text-white';
@endphp
<span class="grid {{ $size ?? 'h-10 w-10 text-lg' }} shrink-0 place-items-center rounded-xl font-black {{ $gradeClasses }}" title="Classe {{ $report->grade }}">{{ $report->grade }}</span>
