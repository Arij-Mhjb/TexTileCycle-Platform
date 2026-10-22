<x-consumer-layout title="{{ $product->name }}">
    <div class="grid gap-8 lg:grid-cols-[.9fr_1.1fr]">
        <div>
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-[460px] w-full rounded-3xl object-cover">
            <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                <strong>Provenance :</strong> les informations produit sont déclarées par la marque. Les preuves vérifiées sont signalées séparément et ne constituent pas une certification.
            </div>
        </div>

        <div>
            <p class="text-sm font-black uppercase tracking-widest text-emerald-700">{{ $product->category }}</p>
            <h1 class="mt-2 text-4xl font-black">{{ $product->name }}</h1>
            <p class="mt-2 text-lg text-slate-600">{{ $product->brandProfile?->name ?? $product->brand ?? 'Non renseigné' }}</p>
            <p class="mt-1 text-xs text-slate-400">Passeport v{{ $product->current_version }} · actualisé le {{ $product->updated_at->format('d/m/Y') }}</p>
            <p class="mt-6 leading-7 text-slate-700">{{ $product->description ?: 'Description non renseignée.' }}</p>

            <dl class="mt-7 grid grid-cols-2 gap-4">
                @foreach ([
                    'Origine'=>$product->origin,
                    'Code-barres'=>$product->barcode,
                    'Score recyclabilité'=>$product->recyclability_score !== null ? $product->recyclability_score.' / 100' : null,
                    'Réparabilité'=>$product->repairable ? 'Oui' : 'Non',
                    'Réutilisable'=>$product->reusable ? 'Oui' : 'Non',
                    'Recyclable'=>$product->recyclable ? 'Oui' : 'Non',
                ] as $label => $value)
                    <div class="rounded-xl bg-slate-100 p-4 dark:bg-slate-900">
                        <dt class="text-xs uppercase text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 font-black">{{ $value ?: 'Non renseigné' }}</dd>
                    </div>
                @endforeach
            </dl>

            <h2 class="mt-8 text-xl font-black">Composition déclarée</h2>
            <div class="mt-3 space-y-2">
                @forelse ($product->materials as $material)
                    <div class="rounded-xl border p-4">
                        <div class="flex justify-between"><span class="font-bold">{{ $material->name }}</span><strong>{{ $material->pivot->percentage ?? '—' }}{{ $material->pivot->percentage !== null ? ' %' : '' }}</strong></div>
                        <p class="mt-2 text-xs text-slate-400">{{ $material->pivot->origin_country ?: 'Origine non renseignée' }} · {{ $material->pivot->supplier ?: 'Fournisseur non renseigné' }}</p>
                    </div>
                @empty
                    <p class="rounded-xl bg-slate-100 p-4">Composition non renseignée.</p>
                @endforelse
            </div>
        </div>
    </div>

    <section class="mt-10 rounded-3xl bg-slate-950 p-7 text-white">
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-300">Meilleure seconde vie proposée</p>
        <h2 class="mt-3 text-2xl font-black">{{ $recommendation['title'] }}</h2>
        <p class="mt-3 max-w-3xl leading-7 text-slate-300">{{ $recommendation['explanation'] }}</p>
        <div class="mt-6 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            @forelse ($points as $point)
                <a href="{{ route('product-returns.create', ['product'=>$product->id,'point'=>$point->id,'action'=>$recommendation['action']]) }}" class="rounded-2xl border border-white/10 bg-white/10 p-4 hover:bg-white/15">
                    <p class="font-black">{{ $point->name }}</p>
                    <p class="mt-1 text-xs text-slate-300">{{ $point->city }} · +{{ $point->recoveryProgram->reward_points }} pts</p>
                </a>
            @empty
                <a href="{{ route('consumer.points', ['type'=>$recommendation['action']]) }}" class="rounded-2xl border border-white/10 bg-white/10 p-4 font-black">Voir les solutions disponibles →</a>
            @endforelse
        </div>
    </section>

    <section class="mt-8 consumer-card p-6">
        <h2 class="text-xl font-black">Instructions de fin de vie</h2>
        <p class="mt-3 leading-7 text-slate-600">{{ $product->recycling_instructions ?: 'Aucune instruction spécifique fournie par la marque.' }}</p>
    </section>
</x-consumer-layout>
