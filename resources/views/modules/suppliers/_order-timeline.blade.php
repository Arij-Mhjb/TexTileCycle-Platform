{{-- Journal de la commande : chaque action de la marque ou du fournisseur, horodatée. --}}
<ol class="relative space-y-5 border-l border-slate-200 pl-6">
    @forelse($order->events as $event)
        <li class="relative">
            <span class="absolute -left-[31px] top-1 grid h-4 w-4 place-items-center rounded-full ring-4 ring-white {{ ['supplier' => 'bg-pink-500', 'brand' => 'bg-purple-700'][$event->actor] ?? 'bg-slate-400' }}"></span>
            <p class="text-xs text-slate-500">{{ $event->created_at->format('d/m/Y à H:i') }} · {{ ['supplier' => $order->supplier->name, 'brand' => $order->brand->name][$event->actor] ?? 'Système' }}</p>
            <p class="mt-0.5 text-sm text-slate-800">{{ $event->message }}</p>
        </li>
    @empty
        <li class="text-sm text-slate-500">Aucun événement enregistré.</li>
    @endforelse
</ol>
