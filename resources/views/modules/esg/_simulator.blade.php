{{-- Simulateur « et si ? » : modifier composition, poids et circularité, voir le score se recalculer.
     Variables : $report, $endpoint (route POST du simulateur) --}}
@php
$product = $report->product;
$allMaterials = \App\Models\Material::orderBy('name')->get(['id', 'name', 'type']);
$initial = [
    'weight_kg' => (float) $report->product_weight_kg,
    'composition' => $product->materials->map(fn ($material) => ['material_id' => $material->id, 'percentage' => (float) ($material->pivot->percentage ?? round(100 / max(1, $product->materials->count())))])->values()->all() ?: [['material_id' => $allMaterials->first()?->id, 'percentage' => 100]],
    'repairable' => (bool) $product->repairable,
    'reusable' => (bool) $product->reusable,
    'recyclable' => (bool) $product->recyclable,
];
@endphp
<section class="shadow-[0_1px_2px_rgba(15,23,42,.05)] mt-6 rounded-2xl border border-sky-200 bg-white p-6 dark:border-sky-900 dark:bg-slate-900"
    x-data="impactSimulator(@js($initial), @js($endpoint), @js(csrf_token()))" x-init="run()">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-[13px] font-semibold text-sky-600">Simulateur</p>
            <h2 class="mt-1 text-lg font-black">Et si on changeait la composition ?</h2>
            <p class="max-w-2xl text-sm text-slate-500">Déplacez les curseurs : le score est recalculé avec la même formule que le rapport officiel. Rien n’est enregistré.</p>
        </div>
        <button type="button" @click="reset()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Réinitialiser</button>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            <template x-for="(row, index) in state.composition" :key="index">
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                    <div class="flex flex-wrap items-center gap-3">
                        <select x-model.number="row.material_id" @change="run()" class="field !mt-0 min-w-0 flex-1" aria-label="Matière">
                            @foreach($allMaterials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }}</option>
                            @endforeach
                        </select>
                        <span class="w-14 text-right text-sm font-black tabular-nums" x-text="row.percentage + ' %'"></span>
                        <button type="button" x-show="state.composition.length > 1" @click="state.composition.splice(index, 1); run()" class="text-sm font-semibold text-rose-600" aria-label="Retirer la matière">Retirer</button>
                    </div>
                    <input type="range" min="0" max="100" step="5" x-model.number="row.percentage" @input="run()" class="mt-3 w-full accent-sky-600" :aria-label="'Pourcentage'">
                </div>
            </template>
            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <button type="button" x-show="state.composition.length < 8" @click="state.composition.push({ material_id: {{ (int) $allMaterials->first()?->id }}, percentage: 10 }); run()" class="font-semibold text-sky-700 hover:underline dark:text-sky-300">+ Ajouter une matière</button>
                <span :class="Math.abs(total() - 100) > 0.5 ? 'text-amber-600' : 'text-slate-500'" x-text="'Total : ' + total() + ' %' + (Math.abs(total() - 100) > 0.5 ? ' (ramené à 100 % dans le calcul)' : '')"></span>
            </div>
            <div class="grid gap-3 sm:grid-cols-4">
                <label class="text-sm font-semibold sm:col-span-1">Poids (kg)
                    <input type="number" min="0.05" max="50" step="0.05" x-model.number="state.weight_kg" @input="run()" class="field">
                </label>
                @foreach(['repairable' => 'Réparable', 'reusable' => 'Réutilisable', 'recyclable' => 'Recyclable'] as $flag => $label)
                    <label class="flex items-center gap-2 self-end rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold dark:border-slate-700">
                        <input type="checkbox" x-model="state.{{ $flag }}" @change="run()" class="rounded text-sky-600"> {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-slate-50 p-5 dark:bg-slate-800/60" :class="loading ? 'opacity-60' : ''">
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl text-3xl font-black" :class="gradeClass(result.grade)" x-text="result.grade ?? '–'"></span>
                    <div>
                        <p class="text-4xl font-black tabular-nums"><span x-text="result.score ?? '–'"></span><span class="text-base text-slate-400">/100</span></p>
                        <p class="text-sm font-bold" :class="(result.delta ?? 0) > 0 ? 'text-emerald-600' : ((result.delta ?? 0) < 0 ? 'text-rose-600' : 'text-slate-500')"
                           x-text="result.delta === undefined ? '' : (result.delta > 0 ? '+' + result.delta : result.delta) + ' point(s) par rapport à aujourd’hui (' + result.baseline + ')'"></p>
                    </div>
                </div>
                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                    <template x-for="item in metrics()" :key="item.label">
                        <div class="rounded-xl bg-white p-3 dark:bg-slate-900">
                            <dt class="text-xs font-semibold text-slate-500" x-text="item.label"></dt>
                            <dd class="mt-1 font-black tabular-nums" x-text="item.value"></dd>
                        </div>
                    </template>
                </dl>
                <p class="mt-3 text-xs text-slate-500" x-show="error" x-text="error"></p>
            </div>
        </div>
    </div>
</section>

@once
<script>
    function impactSimulator(initial, endpoint, token) {
        return {
            initial: JSON.parse(JSON.stringify(initial)),
            state: JSON.parse(JSON.stringify(initial)),
            result: {}, loading: false, error: '', timer: null,
            total() { return Math.round(this.state.composition.reduce((sum, row) => sum + Number(row.percentage || 0), 0) * 10) / 10; },
            reset() { this.state = JSON.parse(JSON.stringify(this.initial)); this.run(); },
            gradeClass(grade) {
                return { A: 'bg-emerald-500 text-white', B: 'bg-lime-500 text-white', C: 'bg-yellow-400 text-slate-900', D: 'bg-orange-500 text-white', E: 'bg-rose-600 text-white' }[grade] || 'bg-slate-300 text-white';
            },
            metrics() {
                const t = this.result.totals || {}; const f = (v, d) => v === undefined ? '–' : Number(v).toLocaleString('fr-FR', { maximumFractionDigits: d });
                return [
                    { label: 'CO₂', value: f(t.co2, 2) + ' kg' }, { label: 'Eau', value: f(t.water, 0) + ' L' },
                    { label: 'Énergie', value: f(t.energy, 1) + ' kWh' }, { label: 'Bonus circularité', value: '+' + (this.result.bonus ?? 0) },
                ];
            },
            run() {
                clearTimeout(this.timer);
                this.timer = setTimeout(async () => {
                    this.loading = true; this.error = '';
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                            body: JSON.stringify(this.state),
                        });
                        if (!response.ok) { this.error = 'Vérifiez le poids et les pourcentages.'; return; }
                        this.result = await response.json();
                    } catch (e) { this.error = 'Simulation indisponible pour le moment.'; }
                    finally { this.loading = false; }
                }, 250);
            },
        };
    }
</script>
@endonce
