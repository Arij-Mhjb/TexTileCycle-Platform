<x-app-layout title="Contrôle des matières">
    <x-ui.page-heading title="Contrôle des retours textiles" description="Comparez les photos, les données déclarées et l’état physique avant la mise à disposition des marques."/>

    <div class="mt-7 space-y-6">
        @forelse ($returns as $return)
            <article class="panel p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">{{ $return->reference }}</p>
                        <h2 class="mt-2 text-2xl font-black">{{ $return->product->name }}</h2>
                        <p class="text-sm text-slate-500">{{ $return->user->name }} · {{ $return->collectionPoint->name }}</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-4 py-2 text-sm font-black text-amber-700">Contrôle attendu</span>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
                    <div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($return->scan?->assets ?? [] as $asset)
                                <figure>
                                    <a href="{{ $asset->url }}" target="_blank"><img src="{{ $asset->url }}" class="h-28 w-full rounded-xl object-cover"></a>
                                    @unless($asset->is_available)<figcaption class="mt-1 text-xs font-semibold text-amber-700">Image originale indisponible après l’ancien déploiement</figcaption>@endunless
                                </figure>
                            @endforeach
                        </div>
                        <div class="mt-5 rounded-2xl bg-slate-50 p-5">
                            <h3 class="font-black">Données Consumer + OCR</h3>
                            <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                <div><dt class="text-slate-400">État</dt><dd class="font-bold">{{ data_get($return->scan?->extracted_data,'condition',$return->condition) }}</dd></div>
                                <div><dt class="text-slate-400">Poids estimé</dt><dd class="font-bold">{{ $return->weight_kg ?? '—' }} kg</dd></div>
                                <div class="sm:col-span-2"><dt class="text-slate-400">Composition proposée</dt><dd class="font-bold">{{ collect(data_get($return->scan?->extracted_data,'composition',[]))->map(fn($x)=>data_get($x,'material').' '.data_get($x,'percentage').'%')->join(' · ') ?: 'Non extraite' }}</dd></div>
                            </dl>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.material-reviews.update', $return) }}" class="rounded-2xl border p-5">
                        @csrf @method('PATCH')
                        <h3 class="font-black">Décision physique assistée</h3>
                        <div class="mt-4 space-y-4">
                            <label class="block text-sm font-bold">État vérifié
                                <select name="condition" class="mt-2 w-full rounded-xl border-slate-300" required>
                                    @foreach(['good'=>'Très bon','wearable'=>'Portable','minor_damage'=>'Petits défauts','damaged'=>'Endommagé','unusable'=>'Inutilisable','unknown'=>'Incertain'] as $value=>$label)
                                        <option value="{{ $value }}" @selected($return->condition === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block text-sm font-bold">Poids confirmé (kg)<input type="number" step="0.01" min="0.01" name="confirmed_weight_kg" value="{{ $return->confirmed_weight_kg ?? $return->weight_kg }}" required class="mt-2 w-full rounded-xl border-slate-300"></label>
                            <label class="block text-sm font-bold">Note de contrôle<textarea name="admin_review_note" rows="3" class="mt-2 w-full rounded-xl border-slate-300"></textarea></label>
                            <div class="grid grid-cols-3 gap-2">
                                <button name="decision" value="verified" class="rounded-xl bg-emerald-600 px-3 py-3 text-sm font-black text-white">Vérifier</button>
                                <button name="decision" value="needs_information" class="rounded-xl bg-amber-100 px-3 py-3 text-sm font-black text-amber-800">Infos</button>
                                <button name="decision" value="rejected" class="rounded-xl bg-rose-100 px-3 py-3 text-sm font-black text-rose-700">Refuser</button>
                            </div>
                        </div>
                    </form>
                </div>
            </article>
        @empty
            <x-ui.empty message="Aucun retour reçu en attente de contrôle."/>
        @endforelse
    </div>
    <div class="mt-8">{{ $returns->links() }}</div>
</x-app-layout>
