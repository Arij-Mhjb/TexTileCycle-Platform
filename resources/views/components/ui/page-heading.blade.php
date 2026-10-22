@props(['title','description'=>null,'action'=>null,'actionLabel'=>'Ajouter'])
<div class="mb-8 flex flex-col gap-5 border-b border-slate-200/70 pb-7 dark:border-slate-800 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">TexTileCycle / Gestion</p><h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h2>@if($description)<p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $description }}</p>@endif</div>
    @if($action)<a href="{{ $action }}" class="btn-primary gap-2"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>{{ $actionLabel }}</a>@endif
</div>
