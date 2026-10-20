<x-consumer-layout :title="$listing->title">
    @include('modules.repair.consumer._tabs')
    <div class="grid gap-8 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl bg-amber-50"><img src="{{ $listing->product?->image_url ?? asset('images/story/clothes-sorting.jpg') }}" alt="{{ $listing->title }}" class="h-full max-h-[32rem] w-full object-cover"></div>
        <div>
            <x-module.badge tone="repair">{{ $listing->gradeLabel() }} · Grade {{ $listing->condition_grade }}</x-module.badge>
            <h1 class="mt-3 text-3xl font-black tracking-tight">{{ $listing->title }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $listing->brand?->name ?? 'TexTileCycle' }} · {{ $listing->statusLabel() }}</p>
            <p class="mt-5 text-4xl font-black text-amber-700">{{ number_format((float) $listing->price, 2, ',', ' ') }} <span class="text-lg">{{ $listing->currency }}</span></p>
            @if($listing->description)<p class="mt-5 leading-7 text-slate-600">{{ $listing->description }}</p>@endif

            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/60 p-5 dark:border-amber-900 dark:bg-amber-950/20">
                <p class="text-[13px] font-semibold text-amber-700">Passeport seconde vie</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @if($listing->product?->materials->isNotEmpty())<li><strong>Composition :</strong> {{ $listing->product->materials->map(fn ($material) => $material->name.($material->pivot->percentage ? ' '.(float) $material->pivot->percentage.' %' : ''))->join(', ') }}</li>@endif
                    @if($listing->repairOrder)<li><strong>Origine :</strong> retour consommateur reconditionné ({{ $listing->repairOrder->reference }})</li>@endif
                    @foreach($listing->repairOrder?->refurbishments ?? [] as $refurbishment)<li><strong>Atelier :</strong> {{ $refurbishment->actions }}</li>@endforeach
                </ul>
            </div>

            @if($listing->isFromConsumer(auth()->user()))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="font-semibold text-emerald-800 dark:text-emerald-300">C’est votre article</p>
                    <p class="mt-1 text-emerald-700 dark:text-emerald-400">Vous l’avez retourné ({{ $listing->repairOrder->reference }}) et il a été reconditionné. Vous avez reçu {{ \App\Services\RepairMarketplaceService::RESALE_POINTS }} points pour sa seconde vie. Les autres membres peuvent maintenant faire une offre.</p>
                    <a href="{{ route('consumer.repairs.index') }}" class="mt-3 inline-block font-semibold text-emerald-800 underline dark:text-emerald-300">Suivre dans Mes réparations</a>
                </div>
            @else
            @if($myOffer)
                <div class="mt-6 rounded-2xl bg-slate-100 p-4 text-sm dark:bg-slate-800">Votre offre : <strong>{{ number_format((float) $myOffer->offered_price, 2, ',', ' ') }} {{ $listing->currency }}</strong> — {{ $myOffer->statusLabel() }}</div>
            @endif
            @if($listing->status === 'published' && (! $myOffer || $myOffer->status !== 'pending'))
                <form method="POST" action="{{ route('consumer.marketplace.offer', $listing) }}" class="mt-6 grid gap-3 sm:grid-cols-3">
                    @csrf
                    <x-module.field name="offered_price" type="number" step="0.01" min="1" label="Votre offre (TND)" :value="(float) $listing->price" required/>
                    <div class="sm:col-span-2"><x-module.field name="message" label="Message (facultatif)" placeholder="Disponible pour un retrait samedi…"/></div>
                    <div class="sm:col-span-3"><x-module.button module="repair" class="w-full">Envoyer mon offre</x-module.button></div>
                </form>
            @endif
            @endif
        </div>
    </div>
</x-consumer-layout>
