<x-app-layout title="Contrôle du passeport">
    @php($data=$proposal->proposed_data ?? [])
    <div class="mx-auto max-w-6xl">
        <a href="{{ route('admin.passport-proposals.index') }}" class="font-black text-emerald-700">← Propositions</a>
        <div class="mt-4 flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Smart Scan #{{ $proposal->scan_id }}</p><h1 class="mt-2 text-3xl font-black">Contrôle de la proposition</h1><p class="mt-2 text-slate-500">{{ $proposal->consumer->name }} · {{ $proposal->statusLabel() }}</p></div><a href="{{ route('scans.show',$proposal->scan) }}" class="rounded-xl border px-4 py-2 font-bold">Voir le scan complet</a></div>
        <section class="mt-7 grid gap-4 sm:grid-cols-3 lg:grid-cols-5">@foreach($proposal->scan->assets as $asset)<figure class="overflow-hidden rounded-2xl border bg-white"><img src="{{ $asset->url }}" class="h-36 w-full object-cover" alt=""><figcaption class="p-3 text-xs font-black uppercase">{{ str($asset->type)->replace('_',' ')->headline() }}</figcaption></figure>@endforeach</section>
        <form method="POST" action="{{ route('admin.passport-proposals.review',$proposal) }}" class="panel mt-7 p-6">@csrf @method('PATCH')
            <div class="grid gap-5 md:grid-cols-2">
                <label class="font-bold">Marque<input name="brand" value="{{ old('brand',data_get($data,'brand')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold">Nom produit<input name="product_name" value="{{ old('product_name',data_get($data,'product_name')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold">SKU<input name="sku" value="{{ old('sku',data_get($data,'sku')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold">Code-barres<input name="barcode" value="{{ old('barcode',data_get($data,'barcode')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold">Catégorie<input name="category" value="{{ old('category',data_get($data,'category')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold">Couleur<input name="color" value="{{ old('color',data_get($data,'color')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold">Taille<input name="size" value="{{ old('size',data_get($data,'size')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold">Pays de fabrication<input name="manufacturing_country" value="{{ old('manufacturing_country',data_get($data,'manufacturing_country')) }}" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label class="font-bold md:col-span-2">Composition — une ligne matière:pourcentage<textarea name="composition_text" rows="4" class="mt-2 w-full rounded-xl border-slate-300">{{ old('composition_text',collect(data_get($data,'composition',[]))->map(fn($x)=>data_get($x,'material').':'.data_get($x,'percentage'))->implode("\n")) }}</textarea></label>
                <label class="font-bold md:col-span-2">Défauts visibles<textarea name="defects_text" rows="3" class="mt-2 w-full rounded-xl border-slate-300">{{ old('defects_text',collect(data_get($data,'defects',[]))->implode("\n")) }}</textarea></label>
                <fieldset class="md:col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5">
                    <legend class="px-2 font-black text-emerald-900">Points partenaires autorisés pour ce produit</legend>
                    <p class="mb-4 text-sm text-emerald-800">Sélection obligatoire pour approuver. Walaeddine ne pourra choisir que ces points, avec l’action circulaire réellement prise en charge par leur programme.</p>
                    @php($selectedPoints = collect(old('collection_point_ids', $proposal->collectionPoints->pluck('id')->all()))->map(fn($id)=>(int)$id))
                    @php($actionLabels = ['repair'=>'Réparation','donation'=>'Don','reuse'=>'Réemploi','take_back'=>'Reprise par la marque','recycling'=>'Recyclage'])
                    <div class="grid gap-4 lg:grid-cols-2">
                        @forelse($collectionPoints->groupBy(fn($point) => $point->recoveryProgram->type) as $type => $partnerPoints)
                            <div class="rounded-xl border bg-white p-4"><p class="mb-3 text-xs font-black uppercase tracking-wider text-emerald-700">{{ $actionLabels[$type] ?? str($type)->headline() }}</p><div class="space-y-3">@foreach($partnerPoints as $point)<label class="flex cursor-pointer items-start gap-3"><input type="checkbox" name="collection_point_ids[]" value="{{ $point->id }}" @checked($selectedPoints->contains($point->id)) class="mt-1 rounded border-slate-300 text-emerald-600"><span><strong class="block">{{ $point->name }}</strong><span class="text-xs text-slate-500">{{ $point->city }} · {{ $point->recoveryProgram->organization_name }} · {{ $point->recoveryProgram->reward_points }} pts</span></span></label>@endforeach</div></div>
                        @empty
                            <p class="text-sm font-bold text-rose-700">Aucun point partenaire actif n’est disponible. Créez ou activez un point avant l’approbation.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('collection_point_ids')" class="mt-3"/>
                </fieldset>
                <label class="font-bold">Affecter à une marque<select name="brand_id" class="mt-2 w-full rounded-xl border-slate-300"><option value="">Aucune marque reconnue</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected($proposal->brand_id===$brand->id)>{{ $brand->name }}</option>@endforeach</select></label>
                <label class="font-bold">Rapprocher d’un produit<select name="product_id" class="mt-2 w-full rounded-xl border-slate-300"><option value="">Créer un produit depuis la proposition</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected($proposal->product_id===$product->id)>{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select></label>
                <label class="font-bold md:col-span-2">Note de décision<textarea name="note" rows="3" class="mt-2 w-full rounded-xl border-slate-300">{{ old('note',$proposal->admin_note) }}</textarea></label>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">@if(in_array($proposal->status,['admin_pending','brand_rejected','consumer_information_required']))<button name="decision" value="approve" class="rounded-xl bg-emerald-700 px-5 py-3 font-black text-white">Approuver avec ces points</button><button name="decision" value="request_information" class="rounded-xl bg-amber-500 px-5 py-3 font-black text-white">Demander des informations</button><button name="decision" value="reject" class="rounded-xl bg-red-700 px-5 py-3 font-black text-white">Refuser</button>@else<button type="submit" formaction="{{ route('admin.passport-proposals.partner-points',$proposal) }}" class="rounded-xl bg-sky-700 px-5 py-3 font-black text-white">Enregistrer les points partenaires</button>@endif</div>
        </form>
        <section class="panel mt-6 p-6"><h2 class="text-xl font-black">Journal du passeport</h2><div class="mt-4 divide-y">@foreach($proposal->events as $event)<div class="py-3 text-sm"><strong>{{ str($event->event)->replace('_',' ')->headline() }}</strong> · {{ $event->actor?->name ?? 'Système' }}<p class="text-xs text-slate-400">{{ $event->created_at->format('d/m/Y H:i') }} · {{ $event->note }}</p></div>@endforeach</div></section>
    </div>
</x-app-layout>
