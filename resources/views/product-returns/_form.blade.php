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

    <section>
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">2 · Solution circulaire</p>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <x-ui.select name="action" label="Action choisie" :value="$item->action ?: 'recycling'" :options="['repair'=>'Réparation','donation'=>'Don','reuse'=>'Réemploi','take_back'=>'Reprise par la marque','recycling'=>'Recyclage']" required/>
            <x-ui.select name="collection_point_id" label="Point partenaire" :value="$item->collection_point_id" :options="$points->mapWithKeys(fn($x)=>[$x->id=>$x->name.' · '.$x->city.' · '.($x->recoveryProgram?->reward_points ?? 0).' pts'])" required/>
        </div>
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

