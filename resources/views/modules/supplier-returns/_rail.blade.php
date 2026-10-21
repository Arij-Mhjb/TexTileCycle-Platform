{{-- Rail vertical du parcours, avec les dates et l'acteur de chaque étape. Variable : $return --}}
@php
$steps = [
    ['Demande de retour', 'Marque', $return->created_at],
    ['Reprise acceptée', 'Fournisseur', $return->accepted_at],
    ['Matière renvoyée', 'Marque', $return->shipped_at],
    ['Matière reçue', 'Fournisseur', $return->received_at],
    ['Seconde vie déclarée', 'Fournisseur', $return->completed_at],
    ['Preuve vérifiée', 'TexTileCycle', $return->verified_at],
];
$current = $return->stepIndex();
@endphp
<nav aria-label="Parcours du retour" class="lg:sticky lg:top-24 lg:self-start">
    <ol class="relative space-y-5 border-l-2 border-teal-100 pl-6 dark:border-teal-900">
        @foreach($steps as $index => [$label, $actor, $date])
            @php $done = $index <= $current && ($date || $index === 0); $next = $index === $current + 1 || ($return->status === 'correction' && $index === 4); @endphp
            <li class="relative">
                <span class="absolute -left-[33px] top-0.5 grid h-5 w-5 place-items-center rounded-full ring-4 ring-white dark:ring-slate-950 {{ $done ? 'bg-teal-600 text-white' : ($next ? 'bg-lime-400 text-lime-950' : 'bg-slate-200 text-slate-400 dark:bg-slate-700') }}">
                    @if($done)<svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.7 13.3 4.4 10l-1.2 1.2 4.5 4.5 9.1-9.1-1.2-1.2z"/></svg>@else<span class="text-[10px] font-black">{{ $index + 1 }}</span>@endif
                </span>
                <p class="text-sm font-bold {{ $done ? 'text-slate-900 dark:text-white' : ($next ? 'text-lime-800 dark:text-lime-300' : 'text-slate-400') }}">{{ $label }}</p>
                <p class="text-xs text-slate-500">{{ $actor }}@if($done && $date) · {{ $date->format('d/m H:i') }}@elseif($next) · en attente @endif</p>
                @if($return->status === 'correction' && $index === 4)<p class="mt-1 text-xs font-bold text-rose-600">Correction demandée</p>@endif
            </li>
        @endforeach
    </ol>
</nav>
