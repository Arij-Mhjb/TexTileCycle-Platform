<x-role-layout title="Résultat Smart Scan">
    @if($scan->status === 'analysing')<meta http-equiv="refresh" content="5">@endif
    <div class="mx-auto max-w-6xl">
        <a href="{{ route('scans.index') }}" class="font-black text-emerald-600">← Historique</a>
        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Smart Scan #{{ $scan->id }}</p><h1 class="mt-2 text-3xl font-black">Analyse du textile</h1></div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-sky-50 px-4 py-2 text-sm font-black text-sky-700">{{ $scan->confidence ?? 0 }} % de confiance · données non vérifiées</span>
                @php($hasReanalysableImage = $scan->assets->contains(fn ($asset) => $asset->is_available))
                @if ($scan->source_type === 'multimodal' && ! $scan->consumer_confirmed_at && $hasReanalysableImage)
                    <form method="POST" action="{{ route('scans.reanalyse', $scan) }}">
                        @csrf
                        <button class="rounded-xl border border-emerald-600 px-4 py-2 text-sm font-black text-emerald-700 hover:bg-emerald-50">
                            Relancer l’analyse
                        </button>
                    </form>
                @elseif ($scan->source_type === 'multimodal' && ! $scan->consumer_confirmed_at)
                    <button disabled title="Aucune image source disponible" class="cursor-not-allowed rounded-xl border border-slate-300 px-4 py-2 text-sm font-black text-slate-400">Relancer l’analyse</button>
                @endif
            </div>
        </div>

        @if ($scan->assets->isNotEmpty())
            <section class="mt-7 grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($scan->assets as $asset)
                    <figure class="overflow-hidden rounded-2xl border bg-white">
                        <img src="{{ $asset->url }}" alt="{{ $asset->type }}" class="h-36 w-full object-cover">
                        <figcaption class="p-3 text-xs font-black uppercase text-slate-500">{{ str($asset->type)->replace('_',' ')->headline() }}</figcaption>
                        @unless ($asset->is_available)
                            <p class="px-3 pb-3 text-xs font-semibold normal-case text-amber-700">Image originale indisponible après l’ancien déploiement</p>
                        @endunless
                    </figure>
                @endforeach
            </section>
        @endif

        @if (auth()->user()->isConsumer() && ! $scan->consumer_confirmed_at)
            @php($data = $scan->extracted_data ?? [])
            @php($aiSource = fn ($field) => data_get($data, 'field_sources.'.$field) === 'gemini')
            @php($fieldClass = fn ($field) => $aiSource($field) ? 'border-amber-400 bg-amber-50' : 'border-slate-300')
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
                @if (data_get($data, 'ai_applied'))
                    <div class="mt-5 rounded-2xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950">
                        <p class="font-black">Suggestions Gemini à vérifier</p>
                        <p class="mt-1">Les champs ambrés ont été complétés depuis les images. Ils ne sont enregistrés qu’après votre confirmation.</p>
                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            @foreach (data_get($data, 'ai_applied_fields', []) as $field)
                                <div class="rounded-xl bg-white/70 px-3 py-2">
                                    <span class="font-black">{{ str($field)->replace('_', ' ')->headline() }}</span>
                                    @if (data_get($data, 'field_confidence.'.$field))
                                        <span class="ml-1 text-violet-700">· {{ data_get($data, 'field_confidence.'.$field) }} %</span>
                                    @endif
                                    @if (data_get($data, 'field_evidence.'.$field))
                                        <p class="mt-1 text-xs text-violet-800">{{ data_get($data, 'field_evidence.'.$field) }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
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
                    <label class="font-bold">Marque<input name="brand" value="{{ old('brand', data_get($data,'brand')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('brand') }}"></label>
                    <label class="font-bold">Nom du produit<input name="product_name" value="{{ old('product_name', data_get($data,'product_name')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('product_name') }}"></label>
                    <label class="font-bold">SKU<input name="sku" value="{{ old('sku', data_get($data,'sku')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('sku') }}"></label>
                    <label class="font-bold">Code-barres<input name="barcode" value="{{ old('barcode', data_get($data,'barcode')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('barcode') }}"></label>
                    <label class="font-bold">Catégorie<input name="category" value="{{ old('category', data_get($data,'category')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('category') }}"></label>
                    <label class="font-bold">Couleur<input name="color" value="{{ old('color', data_get($data,'color')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('color') }}"></label>
                    <label class="font-bold">Taille<input name="size" value="{{ old('size', data_get($data,'size')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('size') }}"></label>
                    <label class="font-bold">Pays de fabrication<input name="manufacturing_country" value="{{ old('manufacturing_country', data_get($data,'manufacturing_country')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('manufacturing_country') }}"></label>
                    <label class="font-bold">Enseigne<input name="retailer" value="{{ old('retailer', data_get($data,'retailer')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('retailer') }}"></label>
                    <label class="font-bold">Date d’achat<input type="date" name="purchase_date" value="{{ old('purchase_date', data_get($data,'purchase_date')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('purchase_date') }}"></label>
                    <label class="font-bold">Prix d’achat<input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', data_get($data,'purchase_price')) }}" class="mt-2 w-full rounded-xl {{ $fieldClass('purchase_price') }}"></label>
                    <label class="font-bold">Devise<input name="currency" maxlength="3" value="{{ old('currency', data_get($data,'currency')) }}" placeholder="EUR, TND…" class="mt-2 w-full rounded-xl uppercase {{ $fieldClass('currency') }}"></label>
                    <label class="font-bold">État
                        <select name="condition" class="mt-2 w-full rounded-xl {{ $fieldClass('condition') }}" required>
                            @foreach(['good'=>'Très bon','wearable'=>'Portable','minor_damage'=>'Petits défauts','damaged'=>'Endommagé','unusable'=>'Inutilisable','unknown'=>'Incertain'] as $value=>$label)
                                <option value="{{ $value }}" @selected(old('condition', data_get($data,'condition','unknown')) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="font-bold md:col-span-2">Composition — une ligne « matière:pourcentage »
                        <textarea name="composition_text" rows="4" class="mt-2 w-full rounded-xl {{ $fieldClass('composition') }}">{{ old('composition_text', collect(data_get($data,'composition',[]))->map(fn($x)=>data_get($x,'material').':'.data_get($x,'percentage'))->implode("\n")) }}</textarea>
                    </label>
                    <label class="font-bold md:col-span-2">Défauts visibles — un par ligne
                        <textarea name="defects_text" rows="3" class="mt-2 w-full rounded-xl {{ $fieldClass('defects') }}">{{ old('defects_text', collect(data_get($data,'defects',[]))->implode("\n")) }}</textarea>
                    </label>
                </div>
                <button class="mt-6 rounded-2xl bg-emerald-700 px-6 py-3 font-black text-white">Confirmer ces informations</button>
            </form>
        @elseif ($scan->passportProposal)
            <section class="mt-7 rounded-3xl border bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Proposition de passeport</p><h2 class="mt-2 text-2xl font-black">{{ $scan->passportProposal->statusLabel() }}</h2><p class="mt-2 max-w-2xl text-slate-500">Les informations sont conservées avec leur provenance et leur historique. Vous serez notifié après chaque décision.</p></div><span class="rounded-full bg-amber-50 px-4 py-2 text-sm font-black text-amber-800">Contrôle en cours</span></div>
                <div class="mt-5 space-y-3 border-l-2 border-emerald-200 pl-5">@foreach($scan->passportProposal->events as $event)<div><p class="font-black">{{ str($event->event)->replace('_',' ')->headline() }}</p><p class="text-xs text-slate-400">{{ $event->created_at->format('d/m/Y H:i') }} · {{ $event->note }}</p></div>@endforeach</div>
                @if($scan->passportProposal->status === 'consumer_information_required')
                    @php($proposalData = $scan->passportProposal->proposed_data ?? [])
                    <div class="mt-6 rounded-2xl bg-amber-50 p-5"><p class="font-black text-amber-900">Demande de TexTileCycle</p><p class="mt-1 text-sm text-amber-800">{{ $scan->passportProposal->admin_note ?: 'Vérifiez et complétez les données ci-dessous.' }}</p></div>
                    <form method="POST" action="{{ route('scans.supplement-data',$scan) }}" class="mt-6 grid gap-4 md:grid-cols-2">
                        @csrf @method('PATCH')
                        @foreach(['brand'=>'Marque','product_name'=>'Produit','sku'=>'Référence / SKU','barcode'=>'Code-barres','category'=>'Catégorie','color'=>'Couleur','size'=>'Taille','manufacturing_country'=>'Pays de fabrication'] as $name=>$label)
                            <x-ui.input :name="$name" :label="$label" :value="data_get($proposalData,$name)"/>
                        @endforeach
                        <x-ui.select name="condition" label="État" :value="data_get($proposalData,'condition','unknown')" :options="collect(['good'=>'Très bon','wearable'=>'Portable','minor_damage'=>'Petits défauts','damaged'=>'Endommagé','unusable'=>'Inutilisable','unknown'=>'Incertain'])" required/>
                        <div></div>
                        <label class="font-bold">Composition — matière:pourcentage<textarea name="composition_text" rows="4" class="mt-2 w-full rounded-xl border-slate-200">{{ collect(data_get($proposalData,'composition',[]))->map(fn($x)=>data_get($x,'material').':'.data_get($x,'percentage'))->implode("\n") }}</textarea></label>
                        <label class="font-bold">Défauts visibles<textarea name="defects_text" rows="4" class="mt-2 w-full rounded-xl border-slate-200">{{ collect(data_get($proposalData,'defects',[]))->implode("\n") }}</textarea></label>
                        <div class="md:col-span-2"><button class="rounded-xl bg-amber-600 px-5 py-3 font-black text-white">Renvoyer les informations</button></div>
                    </form>
                @endif
                @if($scan->passportProposal->isReady() && $scan->passportProposal->product)
                    @if($scan->passportProposal->collectionPoints->isNotEmpty())
                        <a href="{{ route('product-returns.create',['scan'=>$scan->id,'product'=>$scan->passportProposal->product_id]) }}" class="mt-6 inline-flex rounded-xl bg-emerald-700 px-5 py-3 font-black text-white">Préparer le retour circulaire</a>
                    @else
                        <p class="mt-6 rounded-xl bg-amber-50 p-4 text-sm font-bold text-amber-800">Passeport validé, mais aucun point partenaire n’a encore été affecté par TexTileCycle.</p>
                    @endif
                @endif
            </section>
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

