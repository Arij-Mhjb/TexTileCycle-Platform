<x-app-layout title="Facteurs d’impact matières">
    <x-module.hero module="esg" title="Facteurs d’impact par matière" description="Valeur par kg de matière pour chaque catégorie. Sans facteur saisi, une valeur moyenne par type de matière est appliquée."/>
    @include('modules.esg.admin._tabs')

    <form method="POST" action="{{ route('admin.impact-metrics.store') }}" class="panel mb-6 grid gap-4 p-6 md:grid-cols-5">
        @csrf
        <x-ui.select name="material_id" label="Matière" :options="$materials->pluck('name', 'id')" required/>
        <x-ui.select name="impact_category_id" label="Catégorie" :options="$categories->mapWithKeys(fn ($category) => [$category->id => $category->name.' ('.$category->unit.'/kg)'])" required/>
        <x-module.field name="value_per_kg" type="number" step="0.0001" min="0" label="Valeur par kg" required/>
        {{-- Source : une base ACV reconnue, ou « Autre » à préciser --}}
        <div x-data="{ source: @js(old('source', '')) }" class="space-y-2">
            <label class="block"><span class="text-sm font-bold text-slate-700">Source</span>
                <select name="source" x-model="source" class="field">
                    <option value="">Non précisée</option>
                    @foreach(\App\Models\ImpactMetric::SOURCES as $source)<option value="{{ $source }}">{{ $source }}</option>@endforeach
                    <option value="other">Autre…</option>
                </select>
                @error('source')<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
            </label>
            <div x-cloak x-show="source === 'other'">
                <input type="text" name="source_other" value="{{ old('source_other') }}" placeholder="Précisez la source" class="field mt-0" aria-label="Autre source">
                @error('source_other')<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex items-end"><x-module.button module="esg" class="w-full">Enregistrer</x-module.button></div>
    </form>

    <div class="panel overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left text-[13px] font-bold text-slate-400">Matière</th>@foreach($categories as $category)<th class="px-5 py-3 text-left text-[13px] font-bold text-slate-400">{{ $category->name }}<br><span class="normal-case tracking-normal text-slate-300">{{ $category->unit }}/kg</span></th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($materials as $material)
                    <tr>
                        <td class="px-5 py-3"><p class="font-bold">{{ $material->name }}</p><p class="text-xs text-slate-400">{{ $material->type }}</p></td>
                        @foreach($categories as $category)
                            @php $metric = $material->impactMetrics->firstWhere('impact_category_id', $category->id); @endphp
                            <td class="px-5 py-3">
                                @if($metric)
                                    <div class="flex items-center gap-2"><span class="font-black text-sky-700">{{ rtrim(rtrim(number_format((float) $metric->value_per_kg, 4, '.', ' '), '0'), '.') }}</span>
                                        <form method="POST" action="{{ route('admin.impact-metrics.destroy', $metric) }}">@csrf @method('DELETE')<button class="text-xs text-rose-500 hover:underline" title="Supprimer">✕</button></form></div>
                                    @if($metric->source)<p class="text-[11px] text-slate-400">{{ $metric->source }}</p>@endif
                                @else
                                    <span class="text-slate-400" title="Valeur par défaut du type">{{ $defaults[$material->type][$category->code] ?? '—' }} <span class="text-[10px]">défaut</span></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
