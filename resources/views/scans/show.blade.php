<x-role-layout title="Résultat Smart Scan">
    <div class="mx-auto max-w-6xl">
        <a href="{{ route('scans.index') }}" class="font-black text-emerald-600">← Historique</a>
        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Smart Scan #{{ $scan->id }}</p><h1 class="mt-2 text-3xl font-black">Analyse du textile</h1></div>
            <span class="rounded-full bg-sky-50 px-4 py-2 text-sm font-black text-sky-700">{{ $scan->confidence ?? 0 }} % de confiance · données non vérifiées</span>
        </div>

        @if ($scan->assets->isNotEmpty())
            <section class="mt-7 grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($scan->assets as $asset)
                    <figure class="overflow-hidden rounded-2xl border bg-white">
                        <img src="{{ $asset->url }}" alt="{{ $asset->type }}" class="h-36 w-full object-cover">
                        <figcaption class="p-3 text-xs font-black uppercase text-slate-500">{{ str($asset->type)->replace('_',' ')->headline() }}</figcaption>
                    </figure>
                @endforeach
            </section>
        @endif

        @if (auth()->user()->isConsumer() && ! $scan->consumer_confirmed_at)
            @php($data = $scan->extracted_data ?? [])
            <form method="POST" action="{{ route('scans.confirm-data', $scan) }}" class="panel mt-7 p-6">
                @csrf
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="text-xl font-black">Confirmez les données proposées</h2><p class="mt-1 text-sm text-slate-500">{{ $scan->ai_explanation }}</p></div>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700">À confirmer</span>
                </div>
                @if (filled(data_get($data, 'warnings')))
                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <p class="font-black">Incohérences détectées</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach (data_get($data, 'warnings', []) as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label class="font-bold">Produit du catalogue
                        <select name="product_id" class="mt-2 w-full rounded-xl border-slate-300">
                            <option value="">Produit non trouvé</option>
                            @foreach ($catalogueProducts as $product)
                                <option value="{{ $product->id }}" @selected((int) old('product_id', $scan->product_id) === $product->id)>{{ $product->name }} · {{ $product->brand }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="font-bold">Marque<input name="brand" value="{{ old('brand', data_get($data,'brand')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Nom du produit<input name="product_name" value="{{ old('product_name', data_get($data,'product_name')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">SKU<input name="sku" value="{{ old('sku', data_get($data,'sku')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Code-barres<input name="barcode" value="{{ old('barcode', data_get($data,'barcode')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Catégorie<input name="category" value="{{ old('category', data_get($data,'category')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Couleur<input name="color" value="{{ old('color', data_get($data,'color')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Taille<input name="size" value="{{ old('size', data_get($data,'size')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Enseigne<input name="retailer" value="{{ old('retailer', data_get($data,'retailer')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Date d’achat<input type="date" name="purchase_date" value="{{ old('purchase_date', data_get($data,'purchase_date')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Prix d’achat<input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', data_get($data,'purchase_price')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                    <label class="font-bold">Devise<input name="currency" maxlength="3" value="{{ old('currency', data_get($data,'currency')) }}" placeholder="EUR, TND…" class="mt-2 w-full rounded-xl border-slate-300 uppercase"></label>
                    <label class="font-bold">État
                        <select name="condition" class="mt-2 w-full rounded-xl border-slate-300" required>
                            @foreach(['good'=>'Très bon','wearable'=>'Portable','minor_damage'=>'Petits défauts','damaged'=>'Endommagé','unusable'=>'Inutilisable','unknown'=>'Incertain'] as $value=>$label)
                                <option value="{{ $value }}" @selected(old('condition', data_get($data,'condition','unknown')) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="font-bold md:col-span-2">Composition — une ligne « matière:pourcentage »
                        <textarea name="composition_text" rows="4" class="mt-2 w-full rounded-xl border-slate-300">{{ old('composition_text', collect(data_get($data,'composition',[]))->map(fn($x)=>data_get($x,'material').':'.data_get($x,'percentage'))->implode("\n")) }}</textarea>
                    </label>
                    <label class="font-bold md:col-span-2">Défauts visibles — un par ligne
                        <textarea name="defects_text" rows="3" class="mt-2 w-full rounded-xl border-slate-300">{{ old('defects_text', collect(data_get($data,'defects',[]))->implode("\n")) }}</textarea>
                    </label>
                </div>
                <button class="mt-6 rounded-2xl bg-emerald-700 px-6 py-3 font-black text-white">Confirmer ces informations</button>
            </form>
        @elseif ($scan->product)
            <section class="mt-7 grid gap-6 rounded-3xl border bg-white p-6 md:grid-cols-3">
                <img src="{{ $scan->product->image_url }}" class="h-56 w-full rounded-2xl object-cover">
                <div class="md:col-span-2">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">Données confirmées par le consommateur</span>
                    <h2 class="mt-4 text-3xl font-black">{{ $scan->product->name }}</h2>
                    <p class="text-slate-500">{{ $scan->product->brand }}</p>
                    <a class="mt-5 inline-flex font-black text-emerald-600" href="{{ route('consumer.products.show', $scan->product) }}">Consulter le passeport textile →</a>
                </div>
            </section>

            @if (auth()->user()->isConsumer() && $recommendation)
                <section class="mt-6 rounded-3xl bg-slate-950 p-7 text-white">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-300">Recommandation circulaire</p>
                    <h2 class="mt-3 text-2xl font-black">{{ $recommendation['title'] }}</h2>
                    <p class="mt-3 max-w-3xl leading-7 text-slate-300">{{ $recommendation['explanation'] }}</p>
                    <div class="mt-6 grid gap-3 md:grid-cols-2">
                        @forelse ($points as $point)
                            <a href="{{ route('product-returns.create', ['scan'=>$scan->id,'product'=>$scan->product_id,'point'=>$point->id,'action'=>$recommendation['action'],'condition'=>data_get($scan->extracted_data,'condition')]) }}" class="rounded-2xl border border-white/10 bg-white/10 p-4 hover:bg-white/15">
                                <p class="font-black">{{ $point->name }}</p>
                                <p class="mt-1 text-sm text-slate-300">{{ $point->city }} · +{{ $point->recoveryProgram->reward_points }} pts après validation</p>
                            </a>
                        @empty
                            <a href="{{ route('consumer.points', ['type'=>$recommendation['action']]) }}" class="rounded-2xl border border-white/10 bg-white/10 p-4 font-black">Explorer les solutions compatibles →</a>
                        @endforelse
                    </div>
                </section>
            @endif
        @else
            <section class="mt-7 rounded-2xl bg-amber-50 p-6">
                <h2 class="font-black">Produit non relié au catalogue</h2>
                <p class="mt-2">Corrigez les données extraites ou choisissez un produit existant avant de préparer le dépôt.</p>
            </section>
        @endif
    </div>
</x-role-layout>
