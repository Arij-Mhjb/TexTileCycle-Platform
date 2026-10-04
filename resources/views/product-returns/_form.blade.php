<div class="grid gap-5 md:grid-cols-2">
    <x-ui.select name="product_id" label="Produit" :value="$item->product_id" :options="$products->mapWithKeys(fn($x)=>[$x->id=>$x->name.' · '.$x->brand])" required/>
    <x-ui.select name="collection_point_id" label="Point de collecte" :value="$item->collection_point_id" :options="$points->mapWithKeys(fn($x)=>[$x->id=>$x->name.' · '.$x->city])" required/>
    <x-ui.input name="reference" label="Référence du retour" :value="$item->reference" required/>
    @if(auth()->user()->isAdmin())
        <x-ui.select name="status" label="Statut" :value="$item->status ?: 'declared'" :options="['declared'=>'Déclaré','received'=>'Reçu','sorting'=>'Tri','processed'=>'Traité','rejected'=>'Refusé']" required/>
    @else
        <input type="hidden" name="status" value="declared">
        <div class="rounded-2xl bg-sky-50 p-4 text-sm font-semibold text-sky-800">Après votre déclaration, le partenaire confirmera la réception et actualisera le statut.</div>
    @endif
    <x-ui.input name="weight_kg" label="Poids estimé (kg)" type="number" :value="$item->weight_kg" min="0" step="0.01"/>
    <x-ui.input name="returned_at" label="Date prévue ou réelle" type="datetime-local" :value="optional($item->returned_at)->format('Y-m-d\TH:i')"/>
    <div class="md:col-span-2"><x-ui.textarea name="notes" label="Notes" :value="$item->notes"/></div>
</div>
