<x-role-layout title="Résultat Smart Scan">
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('scans.index') }}" class="font-black text-emerald-600">← Historique</a>
        <h1 class="mt-3 text-3xl font-black">Résultat Smart Scan</h1>

        @if ($scan->product)
            <section class="mt-7 grid gap-6 rounded-3xl border bg-white p-6 dark:border-slate-800 dark:bg-slate-900 md:grid-cols-3">
                <img src="{{ $scan->product->image_url }}" class="h-56 w-full rounded-2xl object-cover">
                <div class="md:col-span-2">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">Correspondance {{ $scan->confidence }} %</span>
                    <h2 class="mt-4 text-3xl font-black">{{ $scan->product->name }}</h2>
                    <p class="text-slate-500">{{ $scan->product->brand }}</p>
                    <p class="mt-4">{{ $scan->product->description ?: 'Description non renseignée.' }}</p>
                    @if (auth()->user()->isConsumer())
                        <a class="mt-5 inline-flex font-black text-emerald-600" href="{{ route('consumer.products.show', $scan->product) }}">Consulter le passeport textile →</a>
                    @endif
                </div>
            </section>

            @if (auth()->user()->isConsumer() && $recommendation)
                <section class="mt-6 rounded-3xl bg-slate-950 p-7 text-white">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-300">Recommandation circulaire</p>
                    <h2 class="mt-3 text-2xl font-black">{{ $recommendation['title'] }}</h2>
                    <p class="mt-3 max-w-3xl leading-7 text-slate-300">{{ $recommendation['explanation'] }}</p>

                    <div class="mt-6 grid gap-3 md:grid-cols-2">
                        @forelse ($points as $point)
                            <a href="{{ route('product-returns.create', ['scan'=>$scan->id,'product'=>$scan->product_id,'point'=>$point->id,'action'=>$recommendation['action']]) }}" class="rounded-2xl border border-white/10 bg-white/10 p-4 transition hover:bg-white/15">
                                <p class="font-black">{{ $point->name }}</p>
                                <p class="mt-1 text-sm text-slate-300">{{ $point->city }} · +{{ $point->recoveryProgram->reward_points }} pts après validation</p>
                            </a>
                        @empty
                            <a href="{{ route('consumer.points', ['type'=>$recommendation['action']]) }}" class="rounded-2xl border border-white/10 bg-white/10 p-4 font-black">Explorer les solutions compatibles →</a>
                        @endforelse
                    </div>
                </section>
            @endif
        @elseif ($scan->status === 'multiple_matches')
            <section class="mt-7 rounded-3xl bg-amber-50 p-6">
                <h2 class="text-xl font-black">Plusieurs produits correspondent</h2>
                <p class="mt-2 text-sm text-amber-800">Confirmez le produit avant de recevoir une recommandation.</p>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach ($scan->detections as $detection)
                        <form method="POST" action="{{ route('scans.confirm', [$scan, $detection->product]) }}" class="rounded-2xl bg-white p-4">
                            @csrf
                            <p class="font-black">{{ $detection->detected_name }}</p>
                            <p class="text-sm text-slate-500">{{ $detection->detected_brand }} · {{ $detection->confidence }} %</p>
                            <button class="mt-3 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white">Confirmer</button>
                        </form>
                    @endforeach
                </div>
            </section>
        @else
            <section class="mt-7 rounded-2xl bg-slate-100 p-6">
                <h2 class="font-black">Aucun produit identifié</h2>
                <p class="mt-2">Essayez le code-barres, le QR code ou une recherche manuelle plus précise.</p>
                <a href="{{ route('consumer.products') }}" class="mt-4 inline-flex font-black text-emerald-600">Rechercher dans le catalogue →</a>
            </section>
        @endif
    </div>
</x-role-layout>
