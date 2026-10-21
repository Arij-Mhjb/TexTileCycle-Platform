@php
$kg = fn ($value, $d = 0) => number_format((float) $value, $d, ',', ' ');
$max = max(1, $status['rows']->max('total_kg'));
@endphp
<x-brand-layout :title="$target->name">
    <x-module.hero module="esg" :eyebrow="'Objectif carbone · '.($target->season ?: 'toute la gamme')" :title="$target->name"
        :description="$status['state'] === 'over' ? 'L’objectif est dépassé. Les leviers ci-dessous montrent comment revenir dans le budget.' : 'L’objectif est tenu pour le moment. Ajustez les unités prévues pour simuler la production.'">
        <x-slot:actions>
            <x-module.button module="esg" variant="light" :href="route('brand.carbon-targets.index')">← Tous les objectifs</x-module.button>
        </x-slot:actions>
        <x-slot:stats>
            <x-module.stat label="CO₂e prévu" :value="$kg($status['total_kg'])" hint="kg"/>
            <x-module.stat label="Budget" :value="$status['budget_kg'] ? $kg($status['budget_kg']) : '—'" hint="kg CO₂e"/>
            <x-module.stat label="Consommé" :value="$status['usage'] !== null ? $status['usage'].' %' : '—'"/>
            <x-module.stat label="Après leviers" :value="$status['after_levers_usage'] !== null ? $status['after_levers_usage'].' %' : $kg($status['after_levers_kg'])"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.esg.brand._tabs')

    <section class="panel mb-6 p-6">@include('modules.esg.targets._gauge', ['status' => $status])</section>

    <form method="POST" action="{{ route('brand.carbon-targets.update', $target) }}" class="panel overflow-hidden">
        @csrf @method('PATCH')
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 p-5">
            <div><p class="text-xs font-black uppercase tracking-wider text-sky-600">Plan de production</p><h2 class="mt-1 text-lg font-black">Émissions par produit</h2></div>
            <x-module.button module="esg">Recalculer avec ces unités</x-module.button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800/60">
                    <tr><th class="px-5 py-3">Produit</th><th class="px-3 py-3 text-right">CO₂e / unité</th><th class="px-3 py-3">Unités prévues</th><th class="px-3 py-3">Total</th><th class="px-5 py-3">Levier chiffré</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($status['rows'] as $row)
                        <tr class="{{ $row['over_unit'] ? 'bg-rose-50/50 dark:bg-rose-950/20' : '' }}">
                            <td class="px-5 py-4"><p class="font-bold">{{ $row['product']->name }}</p><p class="text-xs text-slate-500">{{ $row['product']->category }} · classe {{ $row['report']->grade }}</p></td>
                            <td class="px-3 py-4 text-right tabular-nums">
                                <span class="font-bold">{{ $kg($row['unit_kg'], 2) }} kg</span>
                                @if($row['over_unit'])<span class="block text-xs font-bold text-rose-700">au-dessus du plafond</span>@endif
                            </td>
                            <td class="px-3 py-4"><input type="number" min="0" step="1" name="units[{{ $row['product']->id }}]" value="{{ $row['units'] }}" class="field !mt-0 w-28 tabular-nums" aria-label="Unités prévues pour {{ $row['product']->name }}"></td>
                            <td class="px-3 py-4">
                                <p class="font-bold tabular-nums">{{ $kg($row['total_kg']) }} kg</p>
                                <div class="mt-1 h-1.5 w-32 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-1.5 rounded-full bg-sky-500" style="width: {{ max(2, $row['total_kg'] / $max * 100) }}%"></div></div>
                            </td>
                            <td class="px-5 py-4">
                                @if($row['lever'])
                                    <p class="font-semibold">{{ $row['lever']['text'] }}</p>
                                    <p class="text-xs text-sky-700 dark:text-sky-300">−{{ $row['lever']['percent'] }} % par unité ({{ $kg($row['lever']['unit_kg'], 2) }} kg) · <strong>−{{ $kg($row['lever']['saving_kg']) }} kg</strong> au total</p>
                                @else
                                    <p class="text-xs text-slate-400">Déjà en matières recyclées ou sans équivalent recyclé.</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucun rapport d’impact pour cette collection : calculez d’abord l’impact des produits dans le tableau de bord.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('brand.carbon-targets.update', $target) }}" class="panel space-y-4 p-6">
            @csrf @method('PATCH')
            <h2 class="font-black">Paramètres de l’objectif</h2>
            <div class="grid grid-cols-2 gap-3">
                <x-module.field name="budget_kg" type="number" step="1" min="1" label="Budget (kg CO₂e)" :value="$target->budget_kg !== null ? (int) $target->budget_kg : null"/>
                <x-module.field name="max_unit_kg" type="number" step="0.1" min="0.01" label="Plafond / unité (kg)" :value="$target->max_unit_kg !== null ? (float) $target->max_unit_kg : null"/>
            </div>
            <x-module.button module="esg" variant="soft">Enregistrer</x-module.button>
        </form>
        <div class="panel p-6 text-sm leading-6 text-slate-600 dark:text-slate-300">
            <h2 class="font-black text-slate-900 dark:text-white">Comment c’est calculé</h2>
            <p class="mt-2">Émissions = Σ (CO₂e par unité du dernier rapport d’impact × unités prévues). Les leviers recalculent chaque produit avec la même formule que le rapport officiel, en remplaçant les matières vierges par leur équivalent recyclé (coton, polyester, laine, polyamide).</p>
            <form method="POST" action="{{ route('brand.carbon-targets.destroy', $target) }}" class="mt-4" onsubmit="return confirm('Supprimer cet objectif ?')">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-600 hover:underline">Supprimer l’objectif</button></form>
        </div>
    </div>
</x-brand-layout>
