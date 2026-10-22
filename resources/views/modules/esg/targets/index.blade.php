@php $kg = fn ($value) => number_format((float) $value, 0, ',', ' '); @endphp
<x-brand-layout title="Objectifs carbone">
    <x-module.hero module="esg" :eyebrow="'Impact ESG · '.$brand->name" title="Objectifs carbone"
        description="Fixez un budget de CO₂e par collection et un plafond par produit. TexTileCycle additionne l’empreinte de chaque produit × les unités prévues, et chiffre les leviers pour rester dans le budget.">
        <x-slot:stats>
            <x-module.stat label="Objectifs" :value="$targets->count()"/>
            <x-module.stat label="Dépassés" :value="$targets->where('status.state', 'over')->count()"/>
            <x-module.stat label="CO₂e prévu" :value="$kg($targets->sum('status.total_kg'))" hint="kg, tous objectifs"/>
            <x-module.stat label="Économie possible" :value="$kg($targets->sum('status.savings_kg'))" hint="kg CO₂e en recyclé"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.esg.brand._tabs')

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <section class="space-y-4">
            @forelse($targets as ['target' => $target, 'status' => $status])
                <a href="{{ route('brand.carbon-targets.show', $target) }}" class="panel block p-6 transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-lg">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-sky-600">{{ $target->season ?: 'Toute la gamme' }}@if($target->deadline) · échéance {{ $target->deadline->format('d/m/Y') }}@endif</p>
                            <h2 class="mt-1 text-lg font-black">{{ $target->name }}</h2>
                        </div>
                        <p class="text-right text-xs text-slate-500">{{ $status['units'] }} unités prévues @if($target->max_unit_kg)<br>plafond {{ number_format((float) $target->max_unit_kg, 1, ',', ' ') }} kg / unité @endif</p>
                    </div>
                    @include('modules.esg.targets._gauge', ['status' => $status])
                    @if($status['over_unit'])
                        <p class="mt-3 text-sm font-semibold text-rose-700">{{ $status['over_unit'] }} produit(s) au-dessus du plafond par unité.</p>
                    @endif
                </a>
            @empty
                <div class="panel p-10 text-center">
                    <p class="text-lg font-black">Aucun objectif pour le moment</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Créez votre premier objectif : par exemple « AH 2026 : 12 000 kg CO₂e maximum ».</p>
                </div>
            @endforelse
        </section>

        <form method="POST" action="{{ route('brand.carbon-targets.store') }}" class="panel h-fit space-y-4 p-6 lg:sticky lg:top-24">
            @csrf
            <div><p class="text-xs font-black uppercase tracking-wider text-sky-600">Nouveau</p><h2 class="mt-1 text-lg font-black">Créer un objectif</h2></div>
            <x-module.field name="name" label="Nom" placeholder="Collection AH 2026 bas carbone" required/>
            <label class="block text-sm font-bold">Collection
                <select name="season" class="field">
                    <option value="">Toute la gamme</option>
                    @foreach($seasons as $season)<option value="{{ $season }}" @selected(old('season') === $season)>{{ $season }}</option>@endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <x-module.field name="budget_kg" type="number" step="1" min="1" label="Budget total (kg CO₂e)" placeholder="12000"/>
                <x-module.field name="max_unit_kg" type="number" step="0.1" min="0.01" label="Plafond / unité (kg)" placeholder="20"/>
            </div>
            <x-module.field name="deadline" type="date" label="Échéance"/>
            <x-module.button module="esg" class="w-full">Créer l’objectif</x-module.button>
            <p class="text-xs leading-5 text-slate-500">Les émissions viennent des rapports d’impact (CO₂e par unité). Vous indiquerez ensuite les unités prévues par produit.</p>
        </form>
    </div>
</x-brand-layout>
