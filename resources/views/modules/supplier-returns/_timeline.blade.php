{{-- Frise d'un retour fournisseur : $return --}}
@php $current = $return->stepIndex(); @endphp
<ol class="flex items-center gap-1 overflow-x-auto" aria-label="Avancement du retour">
    @foreach(\App\Models\SupplierReturn::STEPS as $key => $label)
        @php $done = $loop->index <= $current; @endphp
        <li class="flex flex-1 items-center gap-1">
            <span class="flex min-w-0 flex-col items-center gap-1 text-center">
                <span class="grid h-8 w-8 place-items-center rounded-full text-xs font-black {{ $done ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-400 dark:bg-slate-800' }}">{{ $done ? '✓' : $loop->iteration }}</span>
                <span class="whitespace-nowrap text-[11px] font-bold {{ $done ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400' }}">{{ $label }}</span>
            </span>
            @unless($loop->last)<span class="mb-5 h-0.5 flex-1 rounded {{ $loop->index < $current ? 'bg-amber-400' : 'bg-slate-200 dark:bg-slate-700' }}"></span>@endunless
        </li>
    @endforeach
</ol>
