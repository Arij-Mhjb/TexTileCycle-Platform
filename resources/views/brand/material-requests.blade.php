<x-brand-layout>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Material Exchange</p><h1 class="mt-2 text-3xl font-black">Demandes de matières</h1><p class="mt-2 text-slate-500">Publiez les matières recherchées pour recevoir des propositions vérifiées.</p></div>
        <a href="{{ route('brand.material-matches.index') }}" class="rounded-xl bg-slate-900 px-5 py-3 font-black text-white">Voir les correspondances</a>
    </div>

    <form method="POST" action="{{ route('brand.material-requests.store') }}" class="mt-7 rounded-3xl border bg-white p-6">
        @csrf
        <h2 class="text-xl font-black">Nouvelle demande</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <label class="text-sm font-bold">Titre<input name="title" required class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Matière recherchée<input name="material_name" placeholder="Coton" required class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Pureté minimale (%)<input type="number" name="minimum_percentage" min="0" max="100" value="70" required class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Quantité recherchée (kg)<input type="number" step="0.1" min="0.1" name="quantity_kg" required class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Bonus de points<input type="number" min="0" name="reward_points" value="50" required class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Date limite<input type="date" name="expires_at" class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Catégories séparées par virgule<input name="categories_text" placeholder="Chemise, Pantalon" class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Ville<input name="city" class="mt-2 w-full rounded-xl border-slate-300"></label>
            <label class="text-sm font-bold">Adresse de dépôt<input name="dropoff_address" class="mt-2 w-full rounded-xl border-slate-300"></label>
            <fieldset class="md:col-span-2 lg:col-span-3"><legend class="text-sm font-bold">États acceptés</legend><div class="mt-2 flex flex-wrap gap-3">@foreach(['good'=>'Très bon','wearable'=>'Portable','minor_damage'=>'Petits défauts','damaged'=>'Endommagé','unusable'=>'Inutilisable'] as $value=>$label)<label class="rounded-full bg-slate-100 px-3 py-2 text-sm font-bold"><input type="checkbox" name="accepted_conditions[]" value="{{ $value }}" @checked(in_array($value,['good','wearable','minor_damage']))> {{ $label }}</label>@endforeach</div></fieldset>
        </div>
        <button class="mt-6 rounded-xl bg-emerald-600 px-6 py-3 font-black text-white">Publier la demande</button>
    </form>

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @forelse ($requests as $item)
            <article class="rounded-2xl border bg-white p-5">
                <div class="flex justify-between gap-3"><div><p class="text-xs font-black uppercase text-emerald-600">{{ $item->material_name }} · ≥ {{ $item->minimum_percentage }}%</p><h2 class="mt-2 text-xl font-black">{{ $item->title }}</h2></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $item->status }}</span></div>
                <p class="mt-4 text-sm text-slate-500">{{ $item->remaining_kg }} / {{ $item->quantity_kg }} kg restants · {{ $item->matches_count }} proposition(s)</p>
                @if($item->status === 'open')<form method="POST" action="{{ route('brand.material-requests.destroy',$item) }}" class="mt-4">@csrf @method('DELETE')<button class="text-sm font-black text-rose-600">Clôturer</button></form>@endif
            </article>
        @empty
            <x-ui.empty message="Aucune demande publiée."/>
        @endforelse
    </div>
    <div class="mt-8">{{ $requests->links() }}</div>
</x-brand-layout>
