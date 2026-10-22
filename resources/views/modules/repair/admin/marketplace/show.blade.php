<x-app-layout :title="$listing->title">
    <x-module.hero module="repair" :eyebrow="'Annonce · '.$listing->gradeLabel()" :title="$listing->title" :description="$listing->description">
        <x-slot:actions>
            <div class="rounded-2xl bg-white/10 p-4 text-right backdrop-blur"><p class="text-3xl font-black">{{ number_format((float) $listing->price, 2, ',', ' ') }}</p>@if($listing->points_price)<p class="font-black text-emerald-200">ou {{ $listing->points_price }} points</p>@endif<p class="text-xs text-amber-100">{{ $listing->currency }} · {{ $listing->statusLabel() }}</p></div>
            @if($listing->status === 'published')<form method="POST" action="{{ route('admin.marketplace.archive', $listing) }}" data-confirm="Archiver cette annonce ?"><button class="rounded-xl border border-white/40 bg-white/10 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/20">Archiver</button>@csrf</form>@endif
        </x-slot:actions>
    </x-module.hero>
    @include('modules.repair.admin._tabs')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel p-6 lg:col-span-2">
            <h2 class="text-lg font-black">Offres reçues</h2>
            <div class="mt-4">@include('modules.repair._offers', ['respondRoute' => 'admin.marketplace.offers.respond', 'soldRoute' => 'admin.marketplace.sold'])</div>
        </section>
        <section class="panel space-y-4 p-6 text-sm">
            <h2 class="text-lg font-black">Traçabilité seconde vie</h2>
            <p><span class="font-bold">Produit :</span> {{ $listing->product?->name ?? '—' }}</p>
            <p><span class="font-bold">Marque :</span> {{ $listing->brand?->name ?? '—' }}</p>
            @if($listing->product?->materials->isNotEmpty())<p><span class="font-bold">Composition :</span> {{ $listing->product->materials->pluck('name')->join(', ') }}</p>@endif
            @if($listing->repairOrder)
                <p><span class="font-bold">Origine :</span> <a class="text-amber-700 underline" href="{{ route('admin.repair-orders.show', $listing->repairOrder) }}">{{ $listing->repairOrder->reference }}</a> (retour de {{ $listing->repairOrder->consumer->name }})</p>
                @foreach($listing->repairOrder->refurbishments as $refurbishment)<p class="rounded-xl bg-amber-50 p-3 text-amber-900">{{ $refurbishment->actions }}</p>@endforeach
            @endif
        </section>
    </div>
</x-app-layout>
