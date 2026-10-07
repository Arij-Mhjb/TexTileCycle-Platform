<x-brand-layout>
<div class="mx-auto max-w-5xl">
    <a href="{{ route('brand.products.index') }}" class="text-sm font-bold text-emerald-700">← Retour au catalogue</a>
    <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-700">Import en lot</p><h1 class="mt-2 text-4xl font-black">Importer des produits</h1><p class="mt-3 max-w-2xl text-slate-600">Créez plusieurs brouillons à partir d’un fichier CSV. Vous pourrez ensuite compléter la composition et les preuves de chaque produit.</p></div>
        <span class="rounded-full bg-amber-50 px-4 py-2 text-sm font-bold text-amber-800">Les produits restent en brouillon</span>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
        <section class="overflow-hidden rounded-3xl border bg-white shadow-sm">
            <div class="border-b bg-slate-50 p-6"><div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-black">Structure du fichier</h2><p class="mt-1 text-sm text-slate-500">La première ligne doit contenir les noms de colonnes ci-dessous.</p></div><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">CSV · UTF-8</span></div>
                <div class="mt-5 overflow-x-auto rounded-2xl bg-slate-950 p-4"><code class="whitespace-nowrap text-sm text-emerald-300">name,sku,category,description,origin,barcode,recyclability_score,recycling_instructions</code></div>
            </div>

            @php($columns = [
                ['name','Nom du produit','Obligatoire','Ex. T-shirt circulaire'],
                ['sku','Référence unique','Obligatoire','Ex. TS-001'],
                ['category','Catégorie','Obligatoire','Ex. T-shirt'],
                ['description','Description','Facultatif','Description du produit'],
                ['origin','Pays d’origine','Facultatif','Ex. Tunisie'],
                ['barcode','Code-barres','Facultatif','Valeur unique'],
                ['recyclability_score','Score de recyclabilité','Facultatif','Nombre de 0 à 100'],
                ['recycling_instructions','Consignes de recyclage','Facultatif','Texte libre'],
            ])
            <div class="divide-y">
                @foreach($columns as [$column,$label,$requirement,$example])
                    <div class="grid gap-2 px-6 py-4 sm:grid-cols-[1.1fr_1fr_auto] sm:items-center">
                        <div><code class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-black text-slate-800">{{ $column }}</code><p class="mt-2 text-sm font-bold">{{ $label }}</p></div>
                        <p class="text-sm text-slate-500">{{ $example }}</p>
                        <span class="w-fit rounded-full px-3 py-1 text-xs font-black {{ $requirement === 'Obligatoire' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600' }}">{{ $requirement }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="space-y-6">
            <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6"><p class="text-xs font-black uppercase tracking-wider text-emerald-700">Avant l’import</p><h2 class="mt-2 text-xl font-black text-emerald-950">3 vérifications rapides</h2><ol class="mt-5 space-y-4 text-sm text-emerald-950">@foreach(['Enregistrez le fichier au format CSV avec un encodage UTF-8.','Gardez name, sku et category remplis sur chaque ligne.','Utilisez un SKU et un code-barres uniques pour chaque produit.'] as $check)<li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-700 text-xs font-black text-white">{{ $loop->iteration }}</span><span>{{ $check }}</span></li>@endforeach</ol></section>

            <section class="rounded-3xl border bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Sélectionner le fichier</h2><p class="mt-2 text-sm text-slate-500">Taille maximale : 5 Mo.</p><form method="POST" enctype="multipart/form-data" action="{{ route('brand.products.import.store') }}" class="mt-5">@csrf<label class="block cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-7 text-center transition hover:border-emerald-500 hover:bg-emerald-50"><span class="block text-3xl">↑</span><span class="mt-2 block font-black">Choisir un fichier CSV</span><span class="mt-1 block text-xs text-slate-500">Cliquez pour parcourir vos fichiers</span><input type="file" name="file" accept=".csv,text/csv" required class="sr-only" onchange="this.parentElement.querySelector('[data-file-name]').textContent = this.files[0]?.name || ''"><strong data-file-name class="mt-3 block break-all text-sm text-emerald-700"></strong></label><x-input-error :messages="$errors->get('file')" class="mt-2"/><button class="mt-5 w-full rounded-xl bg-emerald-700 px-6 py-3 font-black text-white transition hover:bg-emerald-800">Analyser et importer</button></form></section>
        </div>
    </div>
</div>
</x-brand-layout>
