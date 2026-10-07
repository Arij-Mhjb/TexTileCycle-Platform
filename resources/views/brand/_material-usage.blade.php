@php($usage = $match->usage)
<div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
    <h3 class="font-black text-emerald-950">Seconde vie et préparation au label</h3>
    <p class="mt-2 text-sm">Échange consommateur terminé : points validés. La transformation et la revue des preuves restent distinctes.</p>
    @if(!$usage?->label_roadmap_id)
        <form method="POST" action="{{ route('brand.material-usage.assign', $match) }}" class="mt-4 space-y-3">@csrf
            <label class="block text-sm font-bold">Feuille de route et produits concernés
                <select name="roadmap_step_id" required class="mt-2 w-full rounded-xl border-emerald-200"><option value="">Choisir une étape de collecte</option>
                @foreach($roadmaps as $plan)@foreach($plan->steps->where('material_stage','collection') as $target)
                    <option value="{{ $target->id }}">{{ $plan->name }} · {{ $plan->label->name }} · {{ $plan->products->pluck('name')->join(', ') }}</option>
                @endforeach @endforeach</select>
            </label>
            <p class="text-xs text-slate-600">Une matière est affectée à un seul périmètre. Elle pourra être référencée à plusieurs étapes sans additionner plusieurs fois son poids.</p>
            <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white">Associer à ma feuille de route</button>
        </form>
    @else
        <a class="mt-3 block font-bold text-emerald-800" href="{{ route('brand.roadmaps.show', $usage->roadmap) }}">{{ $usage->roadmap->name }} · périmètre v{{ $usage->scope_version }}</a>
        <p class="mt-3 font-bold">{{ $usage->statusLabel() }}</p>
        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
            <div><dt>Entrée pesée</dt><dd class="font-bold">{{ $usage->input_kg ?? 'À confirmer' }} {{ $usage->input_kg !== null ? 'kg' : '' }}</dd></div>
            <div><dt>Sortie recyclée</dt><dd class="font-bold">{{ $usage->recycled_output_kg ?? 'À documenter' }} {{ $usage->recycled_output_kg !== null ? 'kg' : '' }}</dd></div>
            <div><dt>Incorporée aux produits</dt><dd class="font-bold">{{ $usage->incorporated_kg ?? 'À documenter' }} {{ $usage->incorporated_kg !== null ? 'kg' : '' }}</dd></div>
        </dl>
        @php($nextStatus = ['stored'=>'sent','sent'=>'processed','processed'=>'incorporated'][$usage->status] ?? null)
        @if($nextStatus && $usage->roadmap->status === 'active')
            <form method="POST" enctype="multipart/form-data" action="{{ route('brand.material-usage.update', $match) }}" class="mt-5 space-y-4">@csrf
                <input type="hidden" name="status" value="{{ $nextStatus }}">
                <h4 class="font-bold">{{ ['sent'=>'Documenter le transfert au partenaire','processed'=>'Documenter la transformation','incorporated'=>'Documenter l’incorporation'][$nextStatus] }}</h4>
                <label class="block text-sm font-bold">Partenaire, dates, actions et résultats<textarea name="note" required minlength="10" maxlength="4000" rows="3" class="mt-2 w-full rounded-xl border-emerald-200"></textarea></label>
                @if($nextStatus === 'processed')
                    <label class="block text-sm font-bold">Sortie recyclée (kg)<input name="recycled_output_kg" type="number" min="0" step="0.01" max="{{ $usage->input_kg }}" required class="mt-2 w-full rounded-xl border-emerald-200"></label>
                    <p class="text-xs">La différence avec l’entrée pesée correspond aux pertes à expliquer dans le justificatif.</p>
                @elseif($nextStatus === 'incorporated')
                    @foreach($usage->roadmap->products as $product)
                        <label class="block text-sm font-bold">Incorporée dans {{ $product->name }} (kg)<input name="product_quantities[{{ $product->id }}]" type="number" min="0" step="0.01" max="{{ $usage->recycled_output_kg }}" value="0" required class="mt-2 w-full rounded-xl border-emerald-200"></label>
                    @endforeach
                    <p class="text-xs">Le total ne peut pas dépasser {{ $usage->recycled_output_kg }} kg de sortie recyclée.</p>
                @endif
                <label class="block text-sm font-bold">Justificatifs privés<input name="proofs[]" type="file" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.csv,.txt" class="mt-2 block w-full"><span class="text-xs font-normal">1 à 4 fichiers, 8 Mo maximum chacun.</span></label>
                <button class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white">Enregistrer cette étape</button>
            </form>
        @endif
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach($usage->roadmap->steps->whereNotNull('material_stage') as $target)
                @if($target->material_stage === 'collection' || ($target->material_stage === 'transformation' && in_array($usage->status,['processed','incorporated'])) || ($target->material_stage === 'incorporation' && $usage->status === 'incorporated'))
                    <a href="{{ route('brand.roadmaps.show', ['roadmap'=>$usage->roadmap,'material_match_id'=>$match->id,'step_id'=>$target->id]) }}" class="rounded-xl border border-emerald-300 px-3 py-2 text-sm font-bold text-emerald-800">Préparer les preuves : {{ $target->title }}</a>
                @endif
            @endforeach
        </div>
        <p class="mt-4 text-xs text-slate-600">Ces résultats sont documentés par la marque. Le pourcentage de préparation augmente uniquement après approbation des preuves par l’admin.</p>
    @endif
</div>
@if($usage?->label_roadmap_id && $usage->status !== 'stored' && $usage->roadmap->status === 'active')
<details class="mt-3 rounded-xl border p-4"><summary class="cursor-pointer text-sm font-bold">Corriger le bilan ou compléter les justificatifs</summary>
    <p class="mt-3 text-xs text-amber-800">Une correction conserve l’historique et remet les étapes utilisant cette matière à corriger. Leurs preuves devront être soumises et approuvées à nouveau.</p>
    <form method="POST" enctype="multipart/form-data" action="{{ route('brand.material-usage.update',$match) }}" class="mt-3 space-y-3">@csrf
        <input type="hidden" name="status" value="{{ $usage->status }}"><input type="hidden" name="correction" value="1">
        <label class="block text-sm">Motif et résultats corrigés<textarea name="note" required minlength="10" maxlength="4000" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
        @if(in_array($usage->status,['processed','incorporated']))<label class="block text-sm">Sortie recyclée corrigée (kg)<input type="number" name="recycled_output_kg" min="0" max="{{ $usage->input_kg }}" step="0.01" required value="{{ $usage->recycled_output_kg }}" class="mt-1 w-full rounded-xl border-slate-300"></label>@endif
        @if($usage->status === 'incorporated')@foreach($usage->roadmap->products as $product)<label class="block text-sm">Incorporation dans {{ $product->name }} (kg)<input type="number" name="product_quantities[{{ $product->id }}]" min="0" step="0.01" required value="{{ $usage->product_quantities[$product->id] ?? 0 }}" class="mt-1 w-full rounded-xl border-slate-300"></label>@endforeach @endif
        <label class="block text-sm">Nouvelles preuves privées<input type="file" name="proofs[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.csv,.txt" class="mt-2 block w-full"></label>
        <button class="rounded-xl bg-amber-700 px-4 py-2 text-sm font-bold text-white">Enregistrer la correction</button>
    </form>
</details>
@endif
