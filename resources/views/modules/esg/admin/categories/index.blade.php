<x-app-layout title="Catégories d’impact">
    <x-module.hero module="esg" title="Catégories d’impact" description="Indicateurs mesurés et leur poids dans le score environnemental global.">
        <x-slot:actions><x-module.button module="esg" variant="light" :href="route('admin.impact-categories.create')">+ Nouvelle catégorie</x-module.button></x-slot:actions>
    </x-module.hero>
    @include('modules.esg.admin._tabs')
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @forelse($categories as $category)
            <article class="panel p-6">
                <div class="flex items-center justify-between"><x-module.badge tone="esg">{{ $category->code }}</x-module.badge><span class="text-xs font-bold text-slate-400">{{ $category->unit }}</span></div>
                <h2 class="mt-4 text-xl font-black">{{ $category->name }}</h2>
                @if($category->description)<p class="mt-2 text-sm text-slate-500">{{ $category->description }}</p>@endif
                <x-module.bar class="mt-5" module="esg" label="Poids dans le score" :value="(float) $category->weight * 100" :display="(int) round($category->weight * 100).' %'"/>
                <p class="mt-3 text-xs text-slate-400">{{ $category->metrics_count }} facteur(s) matière</p>
                <div class="mt-4 flex gap-2">
                    <a href="{{ route('admin.impact-categories.edit', $category) }}" class="rounded-lg px-3 py-2 text-sm font-bold text-sky-700 hover:bg-sky-50">Modifier</a>
                    <form method="POST" action="{{ route('admin.impact-categories.destroy', $category) }}" onsubmit="return confirm('Supprimer cette catégorie et ses facteurs ?')">@csrf @method('DELETE')<button class="btn-danger">Supprimer</button></form>
                </div>
            </article>
        @empty
            <div class="panel md:col-span-2 xl:col-span-4"><x-ui.empty message="Aucune catégorie. Exécutez le seeder des modules ou créez CO₂, eau, énergie et déchets."/></div>
        @endforelse
    </div>
</x-app-layout>
