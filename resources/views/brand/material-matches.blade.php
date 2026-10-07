<x-brand-layout>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Assistant IA de compatibilité</p>
            <h1 class="mt-2 text-3xl font-black">Retours analysés</h1>
            <p class="mt-2 max-w-3xl text-slate-500">L’IA compare la composition, la pureté, la catégorie et l’état avec les demandes de Primark. Sa proposition aide à décider, mais la marque garde la validation finale.</p>
        </div>
        <a href="{{ route('brand.material-requests.index') }}" class="rounded-xl bg-slate-900 px-5 py-3 text-center font-black text-white">Créer une demande matière</a>
    </div>

    <div class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            [null, 'Toutes', $counts['all'], 'bg-slate-900 text-white'],
            ['compatible', 'Compatibles', $counts['compatible'], 'bg-emerald-100 text-emerald-800'],
            ['partial', 'À vérifier', $counts['partial'], 'bg-amber-100 text-amber-800'],
            ['incompatible', 'Non compatibles', $counts['incompatible'], 'bg-rose-100 text-rose-800'],
        ] as [$value, $label, $count, $colors])
            <a href="{{ route('brand.material-matches.index', $value ? ['verdict' => $value] : []) }}" class="rounded-2xl border p-4 {{ $verdict === $value ? 'ring-2 ring-slate-900' : '' }} {{ $colors }}">
                <span class="text-sm font-bold">{{ $label }}</span><strong class="mt-1 block text-2xl">{{ $count }}</strong>
            </a>
        @endforeach
    </div>

    <div class="mt-7 space-y-5">
        @forelse ($matches as $match)
            @php
                $return = $match->productReturn;
                $verdictLabel = match($match->ai_verdict) { 'compatible' => 'Compatible', 'partial' => 'À vérifier', default => 'Non compatible' };
                $verdictStyle = match($match->ai_verdict) { 'compatible' => 'bg-emerald-100 text-emerald-800', 'partial' => 'bg-amber-100 text-amber-800', default => 'bg-rose-100 text-rose-800' };
                $checks = data_get($match->ai_details, 'checks', []);
            @endphp
            <article class="rounded-3xl border bg-white p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase text-emerald-600">{{ $match->request->title }}</p>
                        <h2 class="mt-2 text-2xl font-black">{{ $return->product->name }}</h2>
                        <p class="text-sm text-slate-500">{{ $return->reference }} · {{ $return->confirmed_weight_kg ?? $return->weight_kg }} kg</p>
                    </div>
                    <div class="text-left lg:text-right">
                        <span class="inline-flex rounded-full px-4 py-2 text-sm font-black {{ $verdictStyle }}">IA : {{ $verdictLabel }}</span>
                        <p class="mt-2 text-xs font-bold text-slate-500">Score {{ $match->match_score }} % · confiance {{ $match->ai_confidence ?? 0 }} %</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_.9fr]">
                    <div>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach(collect($return->scan?->assets ?? [])->where('type','!=','receipt') as $asset)
                                <figure><img src="{{ $asset->url }}" class="h-28 w-full rounded-xl object-cover" alt="Photo du textile"></figure>
                            @endforeach
                        </div>
                        <div class="mt-4 rounded-2xl bg-slate-50 p-4">
                            <p class="text-sm font-black">Pourquoi cette proposition ?</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $match->ai_reason }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach(['material' => 'Matière', 'percentage' => 'Pourcentage', 'category' => 'Catégorie', 'condition' => 'État'] as $key => $label)
                                    @php($passed = (bool) data_get($checks, $key.'.passed', false))
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $passed ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $passed ? '✓' : '✕' }} {{ $label }}</span>
                                @endforeach
                            </div>
                            <p class="mt-3 text-xs text-slate-400">Analyse {{ $match->ai_source === 'gemini' ? 'Gemini (offre gratuite)' : 'locale sans coût' }} · {{ optional($match->ai_analyzed_at)->format('d/m/Y H:i') }}</p>
                        </div>
                        <p class="mt-4 text-sm"><strong>État vérifié :</strong> {{ $return->condition }} · <strong>Admin :</strong> {{ $return->admin_review_note ?: 'Conforme' }}</p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-5">
                        @if($match->status === 'suggested' && $match->ai_verdict === 'compatible')
                            <p class="mb-4 text-sm text-slate-600">L’IA recommande cette correspondance. Vérifiez les preuves avant de réserver.</p>
                            <form method="POST" action="{{ route('brand.material-matches.accept',$match) }}">@csrf<button class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Accepter et réserver</button></form>
                        @elseif($match->status === 'review_needed')
                            <p class="text-sm font-bold text-amber-800">Compatibilité incertaine : une validation humaine est nécessaire. La réservation reste bloquée tant que les critères obligatoires ne sont pas satisfaits.</p>
                        @elseif($match->status === 'incompatible')
                            <p class="text-sm font-bold text-rose-700">Ce retour ne respecte pas les critères de cette demande. Il n’est pas réservable.</p>
                        @elseif(in_array($match->status,['accepted','in_transit']))
                            <p class="text-sm"><strong>QR transfert :</strong><br><span class="break-all font-mono">{{ $match->transfer_token }}</span></p>
                            <form method="POST" action="{{ route('brand.material-matches.receive',$match) }}" class="mt-4">@csrf<button class="w-full rounded-xl bg-sky-600 px-4 py-3 font-black text-white">Confirmer la réception</button></form>
                        @elseif($match->status === 'brand_received')
                            <form method="POST" action="{{ route('brand.material-matches.verify',$match) }}" class="space-y-3">@csrf<textarea name="note" placeholder="Résultat du contrôle final" class="w-full rounded-xl border-slate-300"></textarea><button class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Valider la matière et les points</button></form>
                            <form method="POST" action="{{ route('brand.material-matches.reject',$match) }}" class="mt-3 space-y-3">@csrf<textarea name="note" required placeholder="Motif du refus" class="w-full rounded-xl border-slate-300"></textarea><button class="w-full rounded-xl bg-rose-100 px-4 py-3 font-black text-rose-700">Refuser</button></form>
                        @else
                            <p class="text-sm text-slate-500">{{ $match->brand_review_note ?: str($match->status)->replace('_',' ')->headline() }}</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed bg-white p-10 text-center">
                <h2 class="text-xl font-black">Aucune analyse IA disponible</h2>
                <p class="mx-auto mt-2 max-w-xl text-slate-500">Créez d’abord une demande précisant la matière, le pourcentage minimal, les catégories et les états acceptés. L’IA analysera automatiquement tous les retours déjà vérifiés.</p>
                <a href="{{ route('brand.material-requests.index') }}" class="mt-5 inline-flex rounded-xl bg-emerald-600 px-5 py-3 font-black text-white">Définir les critères Primark</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $matches->links() }}</div>
</x-brand-layout>
