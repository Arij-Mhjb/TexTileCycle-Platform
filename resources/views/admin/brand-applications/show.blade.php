<x-app-layout>
<x-ui.page-heading :title="$application->brand->name" description="Orientation vers les labels et validation du compte marque." />
<a href="{{ route('admin.brand-applications.index') }}" class="mt-3 inline-block text-sm text-emerald-700">← Inscriptions marques</a>
<div class="mt-6 grid items-start gap-6 xl:grid-cols-2">
<section class="rounded-2xl border bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="text-xl font-bold">Questionnaire de la marque</h2>
    <p class="mt-2 text-sm text-slate-500">{{ $application->brand->user->name }} · {{ $application->brand->user->email }}</p>
    <p class="mt-1 text-xs text-slate-500">Dossier #{{ $application->id }} · {{ $application->questionnaire_version }} · reçu le {{ $application->created_at->format('d/m/Y H:i') }}</p>
    <dl class="mt-5 space-y-5">@foreach($application->answers as $key => $value)<div><dt class="text-sm font-bold">{{ $questions[$key]['label'] ?? $key }}</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ $questions[$key]['options'][$value] ?? $value }}</dd></div>@endforeach</dl>
</section>
<div class="space-y-6">
<section class="rounded-2xl border bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="text-xl font-bold">Recommandations IA</h2>
    <p class="mt-2 text-sm text-slate-500">{{ ['pending'=>'Analyse programmée — actualisez après traitement.', 'completed'=>'Analyse terminée', 'unavailable'=>'IA non configurée — revue manuelle possible', 'failed'=>'Analyse en échec — relance possible'][$application->analysis_status] ?? $application->analysis_status }}</p>
    @if($application->analysis_model)<p class="mt-1 text-xs text-slate-500">Modèle : {{ $application->analysis_model }} · {{ $application->analysed_at?->format('d/m/Y H:i') }}</p>@endif
    <p class="mt-3 whitespace-pre-line text-sm">{{ $application->analysis_summary }}</p>
    @forelse($application->recommendations ?? [] as $recommendation)
    <article class="mt-4 rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><p class="text-xs font-bold uppercase text-emerald-700">{{ $recommendation['path'] === 'transition' ? 'Après transformation' : 'Piste à étudier' }}</p><h3 class="mt-1 font-bold">{{ $recommendation['label_name'] }}</h3><p class="mt-2 text-sm">{{ $recommendation['reason'] }}</p>
        <p class="mt-3 text-sm font-bold">Preuves et écarts à vérifier</p><ul class="mt-1 list-disc pl-5 text-sm">@foreach($recommendation['gaps'] as $gap)<li>{{ $gap }}</li>@endforeach</ul>
        <p class="mt-3 text-sm font-bold">Actions proposées</p><ul class="mt-1 list-disc pl-5 text-sm">@foreach($recommendation['next_steps'] as $action)<li>{{ $action }}</li>@endforeach</ul>
        @if($recommendation['official_url'])<a href="{{ $recommendation['official_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block text-sm font-semibold text-emerald-700">Référentiel officiel ↗</a>@endif
    </article>
    @empty<p class="mt-4 text-sm text-slate-500">Aucune piste IA disponible.</p>@endforelse
    @if($application->status === 'pending' && $application->analysis_status !== 'pending')
    <form class="mt-4" method="POST" action="{{ route('admin.brand-applications.analyse', $application) }}">@csrf<x-secondary-button type="submit">Relancer l’analyse IA</x-secondary-button></form>
    @endif
</section>
<section class="rounded-2xl border border-emerald-200 bg-white p-6 dark:bg-slate-900">
    <h2 class="text-xl font-bold">{{ $application->status === 'approved' ? 'Décision enregistrée' : 'Votre décision' }}</h2>
    @if($application->status === 'approved')
    <p class="mt-3 text-sm">Compte activé le {{ $application->reviewed_at->format('d/m/Y H:i') }} par {{ $application->reviewer?->name }}.</p>
    <p class="mt-3 whitespace-pre-line text-sm">{{ $application->review_note }}</p>
    <ul class="mt-3 list-disc pl-5 text-sm">@forelse($application->approved_label_snapshot ?? [] as $label)<li>{{ $label['name'] }}</li>@empty<li>Aucun label retenu.</li>@endforelse</ul>
    @else
    <p class="mt-2 text-sm text-slate-600">Cochez les démarches que la marque pourra poursuivre. Vous pouvez ajouter ou retirer des pistes proposées par l’IA.</p>
    @if($errors->any())<div role="alert" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.brand-applications.approve', $application) }}" class="mt-4">
        @csrf
        <input type="hidden" name="selection_submitted" value="1">
        @foreach($labels as $label)<label class="mb-3 flex items-start gap-3 rounded-lg border p-3"><input type="checkbox" name="labels[]" value="{{ $label->id }}" @checked(in_array($label->id, old('labels', array_column($application->recommendations ?? [], 'label_id')))) class="mt-1 rounded border-gray-300"><span class="text-sm"><strong>{{ $label->name }}</strong><span class="mt-1 block text-slate-500">{{ $label->description }}</span></span></label>@endforeach
        <label for="review_note" class="mt-4 block text-sm font-bold">Motif de votre sélection et conditions à respecter</label>
        <textarea id="review_note" name="review_note" rows="4" required maxlength="5000" class="mt-2 w-full rounded-lg border-gray-300 dark:bg-slate-800">{{ old('review_note') }}</textarea>
        <p class="mt-3 text-xs text-slate-500">L’approbation active le compte et programme l’email à la marque ainsi que le rapport à {{ config('brand_onboarding.audit_email') }}. Elle ne délivre aucun label.</p>
        @if($application->analysis_status === 'pending')
        <p class="mt-5 text-sm font-semibold text-amber-700">La décision sera disponible après le traitement de l’analyse. Actualisez la page une fois le worker exécuté.</p>
        @else
        <x-primary-button class="mt-5">Approuver et activer le compte</x-primary-button>
        @endif
    </form>
    @endif
</section>
</div>
</div>
</x-app-layout>
