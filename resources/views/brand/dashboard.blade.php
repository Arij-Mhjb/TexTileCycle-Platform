<x-brand-layout>
<div class="mb-8"><p class="text-sm font-bold uppercase tracking-widest text-emerald-700">Espace marque</p><h1 class="text-4xl font-black">{{ $brand->name }}</h1><p class="mt-2 text-slate-600">Pilotez vos produits et vos preuves de conformité.</p></div>
<div class="grid gap-5 md:grid-cols-3">
@foreach([['Produits publiés',$publishedProducts],['Dossiers labels',$assessments->count()],['Preuves vérifiées',$verifiedEvidence]] as [$label,$value])
<div class="rounded-2xl border bg-white p-6"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-4xl font-black">{{ $value }}</p></div>
@endforeach
</div>
<div class="mt-8 grid gap-5 md:grid-cols-2"><a class="rounded-2xl bg-emerald-700 p-7 text-white" href="{{ route('brand.products.create') }}"><h2 class="text-xl font-bold">Ajouter un produit</h2><p class="mt-2 text-emerald-100">Composition, traçabilité et recyclabilité.</p></a><a class="rounded-2xl bg-slate-900 p-7 text-white" href="{{ route('brand.labels.index') }}"><h2 class="text-xl font-bold">Explorer les labels</h2><p class="mt-2 text-slate-300">Constituer un dossier et déposer des preuves.</p></a></div>
</x-brand-layout>
