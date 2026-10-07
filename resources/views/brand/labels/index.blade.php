<x-brand-layout>
<h1 class="text-3xl font-black">Vos labels et démarches</h1>
<p class="mt-2 text-slate-600">Les démarches retenues vous aident à préparer vos preuves. TexTileCycle ne délivre aucune certification.</p>
@if($application)
<div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
    <p class="font-bold text-emerald-900">Votre orientation a été validée</p>
    <p class="mt-2 whitespace-pre-line text-sm text-emerald-950">{{ $application->review_note }}</p>
</div>
@endif
<div class="mt-7 grid gap-5 md:grid-cols-2">
@forelse($labels as $label)
@php($assessment=$assessments->get($label->id))
@php($recommendation=collect($application?->recommendations ?? [])->firstWhere('label_id', $label->id))
<a href="{{ route('brand.labels.show',$label) }}" class="rounded-2xl border bg-white p-6 hover:border-emerald-500">
    <p class="text-xs font-bold uppercase text-emerald-700">{{ $recommendation && $recommendation['path'] === 'transition' ? 'Après transformation' : $label->pillar }}</p>
    <h2 class="mt-1 text-xl font-bold">{{ $label->name }}</h2>
    <p class="mt-2 text-sm text-slate-600">{{ $label->description }}</p>
    @if($recommendation)<p class="mt-3 text-sm">{{ $recommendation['reason'] }}</p><ul class="mt-3 list-disc pl-5 text-sm text-slate-600">@foreach($recommendation['next_steps'] as $action)<li>{{ $action }}</li>@endforeach</ul>@endif
    @php($plans=$roadmaps->get($label->id,collect()))
    <div class="mt-4 text-sm">@if($plans->isNotEmpty())<strong>{{ (int)round($plans->avg(fn($plan)=>$plan->progress())) }} % · préparation des feuilles de route</strong><p class="mt-1 text-xs text-slate-500">{{ $plans->count() }} périmètre(s) actif(s), moyenne des étapes approuvées.</p>@else<strong>Préparer ma feuille de route</strong>@endif
    @if($assessment)<p class="mt-2 text-xs text-slate-500">Dossier documentaire général : {{ $assessment->score }} % des preuves pondérées.</p>@endif</div>
</a>
@empty
<p class="rounded-2xl border bg-white p-6 text-slate-600">Aucune démarche retenue pour le moment. Consultez le commentaire de l’administrateur pour préparer la suite.</p>
@endforelse
</div>
</x-brand-layout>

