<x-app-layout>
<x-ui.page-heading title="Roadmaps et soumissions" description="Contrôlez les démarches label, les produits couverts et les preuves envoyées par les marques."/>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
 @foreach([['Roadmaps actives',$activeCount,'bg-emerald-500'],['Soumissions à vérifier',$pendingReviewCount,'bg-amber-500'],['Marques engagées',$brandCount,'bg-violet-500']] as [$label,$value,$color])
 <div class="rounded-2xl border bg-white p-5 dark:bg-slate-900"><div class="flex items-center justify-between"><p class="text-sm text-slate-500">{{ $label }}</p><span class="h-2.5 w-2.5 rounded-full {{ $color }}"></span></div><p class="mt-3 text-3xl font-black">{{ $value }}</p></div>
 @endforeach
</div>

@if($pendingSubmissions->isNotEmpty())
<section class="mt-8 rounded-3xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-900 dark:bg-amber-950/30">
 <div class="flex items-center justify-between"><div><p class="text-xs font-black uppercase tracking-wider text-amber-700">File de contrôle</p><h2 class="mt-1 text-2xl font-black">Soumissions à vérifier</h2></div><span class="rounded-full bg-amber-200 px-3 py-1 text-sm font-black text-amber-900">{{ $pendingSubmissions->count() }}</span></div>
 <div class="mt-5 grid gap-3 lg:grid-cols-2">@foreach($pendingSubmissions as $submission)<a href="{{ route('admin.roadmaps.show',$submission->step->roadmap) }}#step-{{ $submission->step->id }}" class="rounded-2xl border border-amber-200 bg-white p-4 transition hover:border-amber-500 dark:bg-slate-900"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase text-emerald-700">{{ $submission->step->roadmap->brand->name }} · {{ $submission->step->roadmap->label->name }}</p><h3 class="mt-1 font-black">{{ $submission->step->title }}</h3><p class="mt-1 text-xs text-slate-500">{{ $submission->step->roadmap->name }} · soumission v{{ $submission->version }}</p></div><span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">À examiner</span></div><p class="mt-3 text-xs text-slate-500">Envoyée par {{ $submission->submitter?->name }} le {{ $submission->created_at->format('d/m/Y H:i') }} · {{ count($submission->files ?? []) }} preuve(s)</p></a>@endforeach</div>
</section>
@endif

<form class="mt-8 grid gap-3 rounded-2xl border bg-white p-4 dark:bg-slate-900 md:grid-cols-[1fr_220px_auto]"><input name="search" value="{{ request('search') }}" placeholder="Marque, label ou roadmap" class="rounded-xl border-slate-300 dark:bg-slate-950"><select name="status" class="rounded-xl border-slate-300 dark:bg-slate-950"><option value="">Tous les statuts</option><option value="active" @selected(request('status')==='active')>Actives</option><option value="archived" @selected(request('status')==='archived')>Archivées</option></select><button class="rounded-xl bg-slate-900 px-5 py-2 font-black text-white dark:bg-emerald-700">Filtrer</button></form>

<div class="mt-6 grid gap-5 lg:grid-cols-2">@forelse($roadmaps as $roadmap)
@php($pending=$roadmap->steps->flatMap->submissions->where('status','pending')->filter(fn($submission)=>$submission->scope_version===$roadmap->scope_version)->count())
<a href="{{ route('admin.roadmaps.show',$roadmap) }}" class="block overflow-hidden rounded-3xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-slate-900">
 <div class="border-b p-6"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-wider text-emerald-700">{{ $roadmap->brand->name }}</p><h2 class="mt-1 text-xl font-black">{{ $roadmap->label_snapshot['name'] }}</h2><p class="mt-1 text-sm text-slate-500">{{ $roadmap->name }}</p></div>@if($pending)<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">{{ $pending }} à vérifier</span>@else<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $roadmap->status==='archived'?'Archivée':'À jour' }}</span>@endif</div>
 <p class="mt-4 text-sm text-slate-600">{{ $roadmap->products->pluck('name')->join(' · ') }}</p><div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-600" style="width:{{ $roadmap->progress() }}%"></div></div><p class="mt-2 text-xs font-bold">{{ $roadmap->progress() }} % des étapes approuvées · échéance {{ $roadmap->target_date->format('d/m/Y') }}</p></div>
 <div class="grid gap-3 p-5 sm:grid-cols-2">@if($roadmap->recommendation)<div class="rounded-xl bg-violet-50 p-3 text-sm text-violet-900"><strong>Analyse produit-label</strong><p class="mt-1">Préparation {{ $roadmap->recommendation->readiness_score }}/100 · {{ $roadmap->recommendation->analysis_mode==='openai'?'OpenAI + règles':'Règles métier' }}</p></div>@endif<div class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900"><strong>Impact potentiel</strong><p class="mt-1">{{ data_get($roadmap->impact_summary,'environmental.0','À documenter pendant la roadmap.') }}</p></div></div>
</a>
@empty<div class="rounded-2xl border bg-white p-8 lg:col-span-2"><h2 class="font-black">Aucune roadmap trouvée</h2><p class="mt-2 text-sm text-slate-500">Les roadmaps créées par les marques apparaîtront ici.</p></div>@endforelse</div>
<div class="mt-6">{{ $roadmaps->links() }}</div>
</x-app-layout>
