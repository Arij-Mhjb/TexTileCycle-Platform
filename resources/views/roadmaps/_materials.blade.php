@if($roadmap->materialUsages->isNotEmpty())
<section class="mt-6 rounded-2xl border bg-white p-6">
    <h2 class="text-xl font-black">Matières affectées au périmètre</h2>
    <p class="mt-2 text-sm text-slate-600">Chaque retour est compté une seule fois ici. Les quantités transformées et incorporées sont documentées par la marque ; l’approbation des étapes reste administrative.</p>
    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        @foreach(['input_kg'=>'Collectée et pesée','recycled_output_kg'=>'Sortie recyclée documentée','incorporated_kg'=>'Incorporée documentée'] as $field=>$label)
            <div class="rounded-xl bg-emerald-50 p-4"><strong class="text-2xl">{{ number_format($roadmap->materialUsages->sum($field), 2, ',', ' ') }} kg</strong><p class="text-xs">{{ $label }}</p></div>
        @endforeach
    </div>
    @foreach($roadmap->materialUsages as $usage)
        <details class="mt-4 rounded-xl border p-4"><summary class="cursor-pointer font-bold">{{ $usage->match->productReturn->reference }} · {{ $usage->statusLabel() }}</summary>
            <p class="mt-3 text-sm">Entrée : {{ $usage->input_kg ?? 'À peser' }} kg · Sortie : {{ $usage->recycled_output_kg ?? 'À documenter' }} · Incorporation : {{ $usage->incorporated_kg ?? 'À documenter' }}</p>
            @foreach($usage->product_quantities ?? [] as $productId=>$quantity)<p class="mt-2 text-sm">{{ $roadmap->products->firstWhere('id',$productId)?->name ?? 'Produit du périmètre' }} : {{ $quantity }} kg incorporés.</p>@endforeach
            @foreach($usage->events as $event)
                <div class="mt-3 rounded-lg bg-slate-50 p-3"><p class="text-xs font-bold">{{ $event->created_at->format('d/m/Y H:i') }} · {{ ['sent'=>'Transfert','processed'=>'Transformation','incorporated'=>'Incorporation'][$event->status] ?? $event->status }}</p><p class="mt-2 whitespace-pre-line text-sm">{{ $event->note }}</p>
                @foreach($event->files as $file)<a href="{{ route('material-usage.proof', [$event, $loop->index]) }}" class="mt-2 block text-sm font-bold text-emerald-700">{{ $file['name'] ?? 'Justificatif' }}</a>@endforeach</div>
            @endforeach
        </details>
    @endforeach
    @if(auth()->user()->isBrand())<a href="{{ route('brand.material-matches.index') }}" class="mt-4 inline-block font-bold text-emerald-700">Documenter la seconde vie des matières →</a>@endif
</section>
@endif
