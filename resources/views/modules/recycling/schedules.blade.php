@php $kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg'; @endphp
<x-brand-layout title="Collectes programmées">
    @include('modules.recycling._header', [
        'eyebrow' => 'Recyclage · automatisation',
        'title' => 'Collectes programmées',
        'subtitle' => 'Vos chutes de coupe et surplus reviennent chaque mois ? Programmez la collecte une fois : la demande part toute seule chez le fournisseur à la date prévue, avec le prix calculé automatiquement.',
        'actionsHtml' => '<a href="'.route('brand.recycling.index').'" class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-teal-100 ring-1 ring-white/20 hover:bg-white/10">← Mes demandes</a>',
        'chips' => [
            [$schedules->where('active', true)->count(), 'collectes actives'],
            [$kg($schedules->where('active', true)->sum(fn ($s) => $s->totalKg() * ($s->frequency === 'monthly' ? 12 : 4))), 'par an'],
            [$schedules->sum('runs_count'), 'demandes envoyées'],
            [optional($schedules->where('active', true)->sortBy('next_run_on')->first())->next_run_on?->format('d/m') ?? '—', 'prochaine collecte'],
        ],
    ])
    @include('modules.suppliers.brand._tabs')

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
        <section class="space-y-3">
            @forelse($schedules as $schedule)
                <article class="panel flex flex-wrap items-center gap-5 p-5 {{ $schedule->active ? '' : 'opacity-60' }}">
                    <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-teal-50 text-center text-teal-800 ring-1 ring-teal-100 dark:bg-teal-950/40 dark:text-teal-200 dark:ring-teal-900">
                        <span><span class="block text-xl font-black leading-none">{{ $schedule->day_of_month }}</span><span class="text-[10px] font-bold uppercase">{{ $schedule->frequency === 'monthly' ? 'du mois' : 'trim.' }}</span></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-black">{{ $schedule->supplier->name }} <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $schedule->frequencyLabel() }}</span></p>
                        <p class="text-sm text-slate-500">
                            {{ collect($schedule->items)->map(fn ($item) => \App\Models\RecyclingLoopItem::SHORT_KINDS[$item['kind']].' · '.(\App\Models\Material::find($item['material_id'])?->name).' · '.$kg($item['quantity_kg']))->join(' + ') }}
                            · {{ \App\Models\RecyclingLoop::MODES[$schedule->mode] }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            @if($schedule->active)Prochaine demande le <strong class="text-slate-700 dark:text-slate-200">{{ $schedule->next_run_on->format('d/m/Y') }}</strong>@else En pause @endif
                            · {{ $schedule->runs_count }} demande(s) envoyée(s)@if($schedule->last_loop_id) · <a href="{{ route('brand.recycling.show', $schedule->last_loop_id) }}" class="font-bold text-teal-700 hover:underline">dernière demande</a>@endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('brand.recycling-schedules.run', $schedule) }}">@csrf<button class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-bold text-white hover:bg-teal-800">Lancer maintenant</button></form>
                        <form method="POST" action="{{ route('brand.recycling-schedules.toggle', $schedule) }}">@csrf @method('PATCH')<button class="rounded-xl px-4 py-2 text-sm font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:text-slate-200 dark:ring-slate-700">{{ $schedule->active ? 'Pause' : 'Réactiver' }}</button></form>
                        <form method="POST" action="{{ route('brand.recycling-schedules.destroy', $schedule) }}">@csrf @method('DELETE')<button class="rounded-xl px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50" aria-label="Supprimer">✕</button></form>
                    </div>
                </article>
            @empty
                <div class="panel p-10 text-center">
                    <svg class="mx-auto h-12 w-12 text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3v3H8z"/></svg>
                    <p class="mt-3 text-lg font-black">Aucune collecte programmée</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Créez la première avec le formulaire : par exemple 40 kg de chutes de coton le 5 de chaque mois.</p>
                </div>
            @endforelse
        </section>

        <form method="POST" action="{{ route('brand.recycling-schedules.store') }}" class="panel h-fit space-y-4 p-6 lg:sticky lg:top-24" x-data="{ mode: @js(old('mode', 'recover')) }">
            @csrf
            <div><p class="text-xs font-black uppercase tracking-wider text-teal-600">Nouvelle collecte</p><h2 class="mt-1 text-lg font-black">Programmer</h2></div>
            @if($suppliers->isEmpty())
                <p class="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Aucun fournisseur n’a encore activé son espace.</p>
            @else
                <label class="block text-sm font-bold">Fournisseur
                    <select name="supplier_id" class="field" required>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === $supplier->id)>{{ $supplier->name }}</option>@endforeach</select>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm font-bold">Type
                        <select name="kind" class="field"><option value="offcuts" @selected(old('kind') === 'offcuts')>Chutes de coupe</option><option value="surplus" @selected(old('kind') === 'surplus')>Surplus</option></select>
                    </label>
                    <x-module.field name="quantity_kg" type="number" step="0.5" min="1" label="Kilos estimés" placeholder="40" required/>
                </div>
                <label class="block text-sm font-bold">Matière
                    <select name="material_id" class="field" required>@foreach($materials as $material)<option value="{{ $material->id }}" @selected(old('material_id') ? (int) old('material_id') === $material->id : $material->name === 'Coton conventionnel')>{{ $material->name }}</option>@endforeach</select>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm font-bold">Fréquence
                        <select name="frequency" class="field">@foreach(\App\Models\RecyclingSchedule::FREQUENCIES as $value => $label)<option value="{{ $value }}" @selected(old('frequency') === $value)>{{ $label }}</option>@endforeach</select>
                    </label>
                    <x-module.field name="day_of_month" type="number" min="1" max="28" label="Jour du mois" :value="5" required/>
                </div>
                <fieldset>
                    <legend class="text-sm font-bold">En retour</legend>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        @foreach(['recover' => 'Je récupère la matière', 'buyback' => 'Le fournisseur rachète'] as $value => $label)
                            <label class="cursor-pointer rounded-xl border-2 p-3 text-sm font-bold" :class="mode === '{{ $value }}' ? 'border-teal-600 bg-teal-50 dark:bg-teal-950/40' : 'border-slate-200 dark:border-slate-700'">
                                <input type="radio" name="mode" value="{{ $value }}" x-model="mode" class="sr-only">{{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="block text-sm font-bold">Message (facultatif)<textarea name="brand_message" rows="2" class="field">{{ old('brand_message') }}</textarea></label>
                <x-module.button module="loop" class="w-full">Programmer la collecte</x-module.button>
                <p class="text-xs leading-5 text-slate-500">Une tâche planifiée vérifie chaque matin les collectes arrivées à échéance (php artisan schedule:work).</p>
            @endif
        </form>
    </div>
</x-brand-layout>
