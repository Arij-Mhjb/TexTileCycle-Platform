<div class="grid gap-5 md:grid-cols-2">
    <x-ui.select name="source_type" label="Type de source" :value="$item->source_type" :options="['ticket'=>'Ticket','label'=>'Étiquette','barcode'=>'Code-barres','qr'=>'QR code']" required/>
    <x-ui.input name="image_path" label="Image ou référence du document" :value="$item->image_path"/>
    @if(auth()->user()->isAdmin())
        <x-ui.select name="status" label="Statut" :value="$item->status ?: 'pending'" :options="['pending'=>'En attente','processing'=>'Traitement','completed'=>'Terminé','failed'=>'Échec']" required/>
        <x-ui.input name="confidence" label="Confiance (%)" type="number" :value="$item->confidence" min="0" max="100" step="0.01"/>
        <x-ui.input name="scanned_at" label="Date du scan" type="datetime-local" :value="optional($item->scanned_at)->format('Y-m-d\TH:i')"/>
    @else
        <input type="hidden" name="status" value="pending">
        <div class="rounded-2xl bg-emerald-50 p-5 text-sm leading-6 text-emerald-800"><p class="font-black">Analyse intelligente</p><p>Votre document sera placé en attente puis analysé pour identifier le produit, sa marque et sa composition.</p></div>
    @endif
    <div class="md:col-span-2"><x-ui.textarea name="raw_text" label="Texte visible ou informations complémentaires" :value="$item->raw_text" rows="6"/></div>
</div>
