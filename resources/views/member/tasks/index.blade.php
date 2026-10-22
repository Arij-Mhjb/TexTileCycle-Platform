<x-member-layout title="Mes tâches">
@php
    $statusLabels = ['todo'=>'À faire','in_progress'=>'En cours','submitted'=>'En vérification','approved'=>'Validée','changes_requested'=>'À corriger'];
    $statusStyles = ['todo'=>'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200','in_progress'=>'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300','submitted'=>'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300','approved'=>'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300','changes_requested'=>'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'];
    $priorityLabels = ['urgent'=>'Urgente','high'=>'Haute','normal'=>'Normale','low'=>'Basse'];
    $priorityStyles = ['urgent'=>'bg-rose-600 text-white','high'=>'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300','normal'=>'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300','low'=>'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'];
@endphp

<section class="overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-600 p-6 text-white shadow-xl shadow-indigo-100 dark:shadow-none sm:p-8">
    <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <div><p class="text-xs font-black uppercase tracking-[.2em] text-indigo-200">{{ $member->department ?: 'Équipe de certification' }}</p><h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Bonjour {{ $member->name }}</h1><p class="mt-3 max-w-2xl text-sm leading-6 text-indigo-100">Suivez les étapes qui vous sont confiées, consultez les produits concernés et transmettez vos preuves à l’administrateur pour validation.</p></div>
        @if($stats['total'])<div class="min-w-52 rounded-2xl bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur"><div class="flex items-center justify-between text-xs font-bold"><span>Progression globale</span><span>{{ $stats['approved'] }}/{{ $stats['total'] }}</span></div><div class="mt-3 h-2 overflow-hidden rounded-full bg-indigo-950/30"><div class="h-full rounded-full bg-white" style="width: {{ round(($stats['approved'] / $stats['total']) * 100) }}%"></div></div></div>@endif
    </div>
</section>

<section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
    @foreach([['value'=>$stats['todo'],'label'=>'À traiter','color'=>'text-slate-900 dark:text-white'],['value'=>$stats['in_progress'],'label'=>'En cours','color'=>'text-blue-700 dark:text-blue-300'],['value'=>$stats['submitted'],'label'=>'En vérification','color'=>'text-amber-700 dark:text-amber-300'],['value'=>$stats['approved'],'label'=>'Validées','color'=>'text-emerald-700 dark:text-emerald-300'],['value'=>$stats['overdue'],'label'=>'En retard','color'=>'text-rose-700 dark:text-rose-300']] as $stat)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"><p class="text-2xl font-black {{ $stat['color'] }}">{{ $stat['value'] }}</p><p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p></div>
    @endforeach
</section>

<div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Plan de travail</p><h2 class="mt-1 text-2xl font-black">Tâches affectées</h2></div>@if($stats['overdue'])<p class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">{{ $stats['overdue'] }} échéance{{ $stats['overdue'] > 1 ? 's' : '' }} dépassée{{ $stats['overdue'] > 1 ? 's' : '' }}</p>@endif</div>

<div class="mt-5 space-y-8">
@forelse($steps->groupBy('label_roadmap_id') as $roadmapSteps)
    @php($roadmap = $roadmapSteps->first()->roadmap)
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/60 sm:px-6"><div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><div class="flex flex-wrap items-center gap-2"><span class="rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-black uppercase text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ data_get($roadmap->label_snapshot, 'name', $roadmap->label?->name ?? 'Label') }}</span><span class="text-xs font-semibold text-slate-500">{{ $roadmapSteps->count() }} tâche{{ $roadmapSteps->count() > 1 ? 's' : '' }}</span></div><h3 class="mt-2 text-lg font-black">{{ $roadmap->name }}</h3></div><div class="flex flex-wrap gap-2">@foreach($roadmap->products as $product)<span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $product->name }}</span>@endforeach</div></div></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @foreach($roadmapSteps as $step)
            @php($overdue = $step->status !== 'approved' && $step->due_date?->isPast())
            <a href="{{ route('member.tasks.show', $step) }}" class="group grid gap-4 px-5 py-5 transition hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6">
                <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $priorityStyles[$step->priority] ?? $priorityStyles['normal'] }}">{{ $priorityLabels[$step->priority] ?? ucfirst($step->priority) }}</span><span class="rounded-full px-2.5 py-1 text-[11px] font-black {{ $statusStyles[$step->status] ?? $statusStyles['todo'] }}">{{ $statusLabels[$step->status] ?? ucfirst($step->status) }}</span>@if($overdue)<span class="rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-black text-rose-700 dark:bg-rose-950 dark:text-rose-300">En retard</span>@endif</div><h4 class="mt-2 text-base font-black group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ $step->title }}</h4><p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $step->assignment_comment ?: $step->goal }}</p></div>
                <div class="flex items-center justify-between gap-4 sm:justify-end"><div class="text-left sm:text-right"><p class="text-[11px] font-bold uppercase text-slate-400">Échéance</p><p class="mt-1 text-sm font-black {{ $overdue ? 'text-rose-700 dark:text-rose-300' : 'text-slate-700 dark:text-slate-200' }}">{{ $step->due_date?->format('d/m/Y') ?? 'À définir' }}</p></div><span class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 font-black text-slate-500 transition group-hover:bg-indigo-600 group-hover:text-white dark:bg-slate-800">→</span></div>
            </a>
        @endforeach
        </div>
    </section>
@empty
    <section class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-800 dark:bg-slate-900"><div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-indigo-50 text-2xl dark:bg-indigo-950">✓</div><h2 class="mt-4 text-xl font-black">Aucune tâche affectée</h2><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Votre responsable de marque n’a encore affecté aucune étape à votre compte.</p></section>
@endforelse
</div>
</x-member-layout>
