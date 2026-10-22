<x-member-layout :title="$step->title">
@php
    $statusLabels = ['todo'=>'À faire','in_progress'=>'En cours','submitted'=>'En vérification','approved'=>'Validée','changes_requested'=>'À corriger'];
    $statusStyles = ['todo'=>'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-100','in_progress'=>'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-200','submitted'=>'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200','approved'=>'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200','changes_requested'=>'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200'];
    $priorityLabels = ['urgent'=>'Urgente','high'=>'Haute','normal'=>'Normale','low'=>'Basse'];
    $labelName = data_get($step->roadmap->label_snapshot, 'name', $step->roadmap->label?->name ?? 'Label');
    $overdue = $step->status !== 'approved' && $step->due_date?->isPast();
    $inputClass = 'mt-2 block w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-base leading-6 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-950';
@endphp

<a href="{{ route('member.tasks.index') }}" class="inline-flex items-center gap-2 rounded-xl px-1 py-2 text-sm font-black text-indigo-700 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-200"><span aria-hidden="true">←</span> Retour à mes tâches</a>

@if(session('success'))
    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-5 text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200"><p class="font-black">Veuillez corriger les informations suivantes :</p><ul class="mt-2 list-disc space-y-1 pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<section class="mt-4 overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-800 via-indigo-700 to-violet-700 p-6 text-white shadow-xl shadow-indigo-100 dark:shadow-none sm:p-8">
    <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-start">
        <div class="max-w-3xl">
            <div class="flex flex-wrap gap-2"><span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black ring-1 ring-white/20">{{ $labelName }}</span><span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black ring-1 ring-white/20">Priorité {{ strtolower($priorityLabels[$step->priority] ?? $step->priority) }}</span><span class="rounded-full px-3 py-1 text-xs font-black {{ $statusStyles[$step->status] ?? $statusStyles['todo'] }}">{{ $statusLabels[$step->status] ?? ucfirst($step->status) }}</span></div>
            <h1 class="mt-4 text-3xl font-black leading-tight tracking-tight sm:text-4xl">{{ $step->title }}</h1>
            <p class="mt-3 text-sm leading-7 text-indigo-100 sm:text-base">{{ $step->assignment_comment ?: $step->goal }}</p>
        </div>
        <div class="shrink-0 rounded-2xl bg-white/10 p-4 ring-1 ring-white/20"><p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Date limite</p><p class="mt-1 text-xl font-black {{ $overdue ? 'text-rose-200' : 'text-white' }}">{{ $step->due_date?->format('d/m/Y') ?? 'À définir' }}</p>@if($overdue)<p class="mt-1 text-xs font-bold text-rose-200">Échéance dépassée</p>@endif</div>
    </div>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7">
            <p class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Ce qui est attendu</p>
            <h2 class="mt-2 text-xl font-black">Consignes de l’étape</h2>
            <div class="mt-4 space-y-4 text-sm leading-7 text-slate-600 dark:text-slate-300">
                @if($step->instructions)<div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/70"><p class="text-xs font-black uppercase text-slate-400">Instructions</p><p class="mt-1 whitespace-pre-line">{{ $step->instructions }}</p></div>@endif
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/70"><p class="text-xs font-black uppercase text-slate-400">Objectif</p><p class="mt-1 whitespace-pre-line">{{ $step->goal }}</p></div>
                @if($step->required_evidence)<div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"><p class="text-xs font-black uppercase">Preuve demandée</p><p class="mt-1 whitespace-pre-line">{{ $step->required_evidence }}</p></div>@endif
            </div>
        </section>

        @if($step->status !== 'approved')
        <form method="POST" enctype="multipart/form-data" action="{{ route('member.tasks.submit', $step) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7">
            @csrf
            <input type="hidden" name="scope_version" value="{{ $step->roadmap->scope_version }}">
            <div class="border-b border-slate-100 pb-5 dark:border-slate-800"><p class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Votre contribution</p><h2 class="mt-2 text-2xl font-black">Soumettre mon travail</h2><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Décrivez précisément le travail réalisé et joignez au moins une preuve. Votre saisie est conservée si une erreur est détectée.</p></div>

            <div class="mt-6 space-y-6">
                <label class="block"><span class="text-sm font-black text-slate-800 dark:text-slate-100">Actions réalisées <span class="text-rose-600">*</span></span><span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Expliquez les actions concrètes menées pour cette étape.</span><textarea name="actions" required minlength="10" rows="5" placeholder="Exemple : Nous avons contacté trois fournisseurs et vérifié leurs certificats…" class="{{ $inputClass }}">{{ old('actions') }}</textarea></label>

                <label class="block"><span class="text-sm font-black text-slate-800 dark:text-slate-100">Résultats obtenus et écarts <span class="text-rose-600">*</span></span><span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Indiquez ce qui est terminé, mesurable ou encore à corriger.</span><textarea name="results" required minlength="10" rows="5" placeholder="Exemple : 80 % des documents sont conformes ; un certificat reste à renouveler…" class="{{ $inputClass }}">{{ old('results') }}</textarea></label>

                @if($step->roadmap->products->isNotEmpty())
                <fieldset class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700 sm:p-5"><legend class="px-2 text-sm font-black text-slate-800 dark:text-slate-100">Résultat par produit</legend><p class="mb-5 text-xs leading-5 text-slate-500 dark:text-slate-400">Précisez l’avancement de chaque produit couvert par ce label.</p><div class="space-y-5">@foreach($step->roadmap->products as $product)<label class="block"><span class="inline-flex rounded-lg bg-indigo-50 px-2.5 py-1 text-sm font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $product->name }}</span><textarea name="product_results[{{ $product->id }}]" required minlength="5" rows="3" placeholder="Résultat et situation de ce produit…" class="{{ $inputClass }}">{{ old('product_results.'.$product->id) }}</textarea></label>@endforeach</div></fieldset>
                @endif

                <label class="block rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-5 transition hover:border-indigo-400 dark:border-slate-700 dark:bg-slate-800/60"><span class="text-sm font-black text-slate-800 dark:text-slate-100">Documents et preuves <span class="text-rose-600">*</span></span><span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">1 à 4 fichiers : PDF, image, Word, CSV ou texte. Maximum 8 Mo par fichier.</span><input type="file" name="proofs[]" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.csv,.txt" data-field-label="Documents et preuves" data-max-files="4" data-max-bytes="8388608" data-validation-message="Formats acceptés : PDF, JPG, JPEG, PNG, WebP, Word, CSV ou TXT." class="mt-4 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-indigo-600 file:px-4 file:py-2.5 file:font-bold file:text-white hover:file:bg-indigo-700 dark:text-slate-300"></label>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs leading-5 text-slate-500 dark:text-slate-400">La marque pourra vérifier votre soumission avant validation.</p><button class="rounded-2xl bg-indigo-600 px-6 py-3.5 text-sm font-black text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 dark:shadow-none dark:focus:ring-indigo-950">Envoyer ma soumission</button></div>
        </form>
        @else
        <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-7 text-center dark:border-emerald-900 dark:bg-emerald-950/30"><div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-600 text-xl font-black text-white">✓</div><h2 class="mt-3 text-xl font-black text-emerald-900 dark:text-emerald-100">Étape validée</h2><p class="mt-2 text-sm text-emerald-700 dark:text-emerald-300">Votre travail a été approuvé. Aucune nouvelle soumission n’est nécessaire.</p></section>
        @endif
    </div>

    <aside class="space-y-4 lg:sticky lg:top-6 lg:self-start">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-black uppercase tracking-wider text-slate-400">Feuille de route</p><h2 class="mt-2 font-black">{{ $step->roadmap->name }}</h2><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $labelName }}</p></section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><div class="flex items-center justify-between"><p class="text-xs font-black uppercase tracking-wider text-slate-400">Produits concernés</p><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-black dark:bg-slate-800">{{ $step->roadmap->products->count() }}</span></div><div class="mt-3 space-y-2">@forelse($step->roadmap->products as $product)<div class="rounded-xl bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $product->name }}</div>@empty<p class="text-sm text-slate-500">Aucun produit renseigné.</p>@endforelse</div></section>
        @if($step->submissions->isNotEmpty())<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-xs font-black uppercase tracking-wider text-slate-400">Historique</p><div class="mt-3 space-y-3">@foreach($step->submissions as $submission)<div class="border-l-2 {{ $submission->status === 'approved' ? 'border-emerald-500' : ($submission->status === 'rejected' ? 'border-rose-500' : 'border-amber-500') }} pl-3"><p class="text-sm font-black">Soumission v{{ $submission->version }}</p><p class="text-xs text-slate-500">{{ $submission->created_at->format('d/m/Y à H:i') }} · {{ ucfirst($submission->status) }}</p>@if($submission->review_comment)<p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $submission->review_comment }}</p>@endif</div>@endforeach</div></section>@endif
    </aside>
</div>
</x-member-layout>
