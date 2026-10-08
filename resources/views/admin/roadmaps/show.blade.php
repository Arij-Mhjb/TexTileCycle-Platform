<x-app-layout>
<a href="{{ route('admin.roadmaps.index') }}" class="text-sm font-bold text-emerald-700">← Feuilles de route</a><p class="mt-3 font-bold">{{ $roadmap->brand->name }}</p>
@include('roadmaps._summary')
@if($errors->any())<div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="mt-7 space-y-5">@foreach($roadmap->steps as $step)<section id="step-{{ $step->id }}" class="scroll-mt-6 rounded-2xl border bg-white p-6"><p class="text-xs font-bold text-emerald-700">ÉTAPE {{ $step->position }} · {{ $step->due_date->format('d/m/Y') }} · {{ $step->owner_name }}</p><h2 class="mt-2 text-xl font-black">{{ $step->title }}</h2><p class="mt-3 text-sm"><strong>Objectif :</strong> {{ $step->goal }}</p><p class="mt-2 text-sm"><strong>Preuves attendues :</strong> {{ $step->required_evidence }}</p>
@forelse($step->submissions as $submission)
@include('roadmaps._submission')
@if($roadmap->status==='active'&&$step->status==='submitted'&&$submission->status==='pending'&&$submission->scope_version===$roadmap->scope_version&&$loop->first)
<form method="POST" action="{{ route('admin.roadmaps.review',$submission) }}" class="mt-4 space-y-3 rounded-xl bg-emerald-50 p-4">@csrf @method('PATCH')<h3 class="font-bold">Examiner cette version</h3><label class="block text-sm">Décision<select name="status" required class="mt-1 w-full rounded-lg border-emerald-200"><option value="">Choisir</option><option value="approved">Preuves approuvées pour cette étape</option><option value="rejected">Corrections demandées</option></select></label><label class="block text-sm">Motif et conditions<textarea name="review_comment" required maxlength="4000" rows="3" class="mt-1 w-full rounded-lg border-emerald-200"></textarea></label><p class="text-xs">Vérifiez les résultats par produit, les preuves et les poids. L’approbation constitue une revue interne, pas l’attribution du label.</p><button class="rounded-lg bg-emerald-700 px-4 py-2 font-bold text-white">Enregistrer la décision</button></form>
@endif
@empty<p class="mt-4 text-sm text-slate-500">Aucune preuve soumise.</p>@endforelse
</section>@endforeach</div>
</x-app-layout>
