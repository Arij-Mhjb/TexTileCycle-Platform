<x-app-layout :title="'Impact · '.$report->product->name">
    @vite('resources/js/esg-charts.js')
    <x-module.hero module="esg" :eyebrow="'Rapport d’impact · '.($report->brand?->name ?? 'Sans marque')" :title="$report->product->name" :description="'Calculé le '.$report->created_at->format('d/m/Y à H:i').($report->author ? ' par '.$report->author->name : '').'.'">
        <x-slot:actions>
            <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3 backdrop-blur">@include('modules.esg._grade', ['report' => $report, 'size' => 'h-14 w-14 text-2xl'])<div><p class="text-3xl font-black">{{ $report->impact_score }}<span class="text-base text-sky-200">/100</span></p><p class="text-xs text-sky-100">{{ $report->status === 'validated' ? 'Validé' : 'Brouillon' }}</p></div></div>
        </x-slot:actions>
    </x-module.hero>
    @include('modules.esg.admin._tabs')

    @include('modules.esg._report')

    <section class="panel mt-6 p-6">
        @if($report->status === 'validated')
            <p class="text-sm"><strong>Validé</strong> par {{ $report->validator?->name }} le {{ $report->validated_at?->format('d/m/Y') }}@if($report->assessment) · déclaration appuyée par l’évaluation « {{ $report->assessment->organization_name }} · {{ $report->assessment->framework }} »@endif.</p>
            @if($report->notes)<p class="mt-2 text-sm text-slate-500">{{ $report->notes }}</p>@endif
        @else
            <form method="POST" action="{{ route('admin.impact-reports.validate', $report) }}" class="grid gap-4 md:grid-cols-3">
                @csrf @method('PATCH')
                <div class="md:col-span-3"><p class="text-xs font-black uppercase tracking-wider text-sky-600">Validation</p><h2 class="mt-1 text-lg font-black">Valider les déclarations d’impact</h2></div>
                <x-ui.select name="assessment_id" label="Évaluation de conformité associée" :options="$assessments" placeholder="Aucune"/>
                <div class="md:col-span-2"><x-module.field name="notes" label="Commentaire" placeholder="Données vérifiées avec les preuves fournisseurs…"/></div>
                <div class="md:col-span-3 flex justify-end"><x-module.button module="esg">Valider le rapport</x-module.button></div>
            </form>
        @endif
    </section>
</x-app-layout>
