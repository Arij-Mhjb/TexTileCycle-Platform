<div class="space-y-7">
    @if ($item->scan_id)
        <input type="hidden" name="scan_id" value="{{ $item->scan_id }}">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
            Ce retour est relié au Smart Scan #{{ $item->scan_id }}.
        </div>
    @endif

    <section>
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">1 · Textile</p>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <x-ui.select name="product_id" label="Produit" :value="$item->product_id" :options="$products->mapWithKeys(fn($x)=>[$x->id=>$x->name.' · '.$x->brand])" required/>
            <x-ui.select name="condition" label="État du textile" :value="$item->condition ?: 'unknown'" :options="['good'=>'Très bon état','wearable'=>'Encore portable','minor_damage'=>'Petite réparation nécessaire','damaged'=>'Endommagé','unusable'=>'Inutilisable','unknown'=>'Je ne sais pas']"/>
        </div>
    </section>

    <section x-data="{ action: @js(old('action', $item->action)), point: @js((string) old('collection_point_id', $item->collection_point_id)), points: @js($pointOptions) }">
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">2 · Solution circulaire</p>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <label class="block"><span class="text-sm font-bold text-slate-700">Action choisie <span class="text-rose-500">*</span></span><select name="action" x-model="action" @change="point = ''" required class="field"><option value="">Sélectionner</option>@foreach($allowedActions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><x-input-error :messages="$errors->get('action')" class="mt-1"/></label>
            <label class="block"><span class="text-sm font-bold text-slate-700">Point partenaire <span class="text-rose-500">*</span></span><select name="collection_point_id" x-model="point" required class="field"><option value="">Sélectionner un point compatible</option><template x-for="partnerPoint in points.filter(item => item.type === action)" :key="partnerPoint.id"><option :value="String(partnerPoint.id)" x-text="partnerPoint.label"></option></template></select><x-input-error :messages="$errors->get('collection_point_id')" class="mt-1"/></label>
        </div>
        @if($points->isEmpty())<p class="mt-3 rounded-xl bg-amber-50 p-4 text-sm font-bold text-amber-800">Aucun point partenaire n’a été autorisé pour ce passeport. Contactez TexTileCycle.</p>@endif
    </section>

    <section>
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">3 · Préparation du dépôt</p>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <x-ui.input name="weight_kg" label="Poids estimé (kg)" type="number" :value="$item->weight_kg" min="0" step="0.01"/>
            <x-ui.input name="returned_at" label="Date prévue" type="datetime-local" :value="optional($item->returned_at)->format('Y-m-d\TH:i')"/>
            <div class="md:col-span-2"><x-ui.textarea name="notes" label="Informations utiles pour le partenaire" :value="$item->notes"/></div>
        </div>
    </section>

    @if (auth()->user()->isAdmin() && $item->exists)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-xs font-black uppercase tracking-[.2em] text-amber-700">Validation partenaire / administration</p>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <x-ui.select name="status" label="Nouveau statut" :value="$item->status" :options="['declared'=>'Déclaré','dropoff_expected'=>'Dépôt attendu','deposit_declared'=>'Dépôt déclaré','received'=>'Reçu','inspected'=>'Contrôlé','routed'=>'Orienté','completed'=>'Terminé','rejected'=>'Refusé','cancelled'=>'Annulé']" required/>
                <x-ui.input name="confirmed_weight_kg" label="Poids confirmé (kg)" type="number" :value="$item->confirmed_weight_kg" min="0" step="0.01"/>
                <div class="md:col-span-2"><x-ui.textarea name="status_note" label="Note de traçabilité"/></div>
            </div>
        </section>
    @endif
</div>

