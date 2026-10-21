<x-brand-layout>
    @vite('resources/js/esg-charts.js')
    <x-module.hero module="esg" eyebrow="Rapport d’impact produit" :title="$report->product->name" :description="'Calculé le '.$report->created_at->format('d/m/Y').($report->status === 'validated' ? ' · validé par TexTileCycle le '.$report->validated_at?->format('d/m/Y') : ' · en attente de validation par TexTileCycle')">
        <x-slot:actions>
            <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3 backdrop-blur">@include('modules.esg._grade', ['report' => $report, 'size' => 'h-14 w-14 text-2xl'])<div><p class="text-3xl font-black">{{ $report->impact_score }}<span class="text-base text-sky-200">/100</span></p></div></div>
            <x-module.button module="esg" variant="light" :href="route('brand.impact.index')">← Tableau de bord</x-module.button>
        </x-slot:actions>
    </x-module.hero>
    @include('modules.esg.brand._tabs')
    @include('modules.esg._report')
    @include('modules.esg._simulator', ['endpoint' => route('brand.impact.simulate', $report)])
    @include('modules.esg._ai-recommendations', ['action' => route('brand.impact.ai-recommendations', $report)])
</x-brand-layout>
