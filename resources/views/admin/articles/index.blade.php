<x-app-layout title="Articles du blog">
    <x-ui.page-heading title="Articles du blog" description="Rédigez et publiez les articles de sensibilisation." :action="route('admin.articles.create')" action-label="Nouvel article"/>
    <div class="panel overflow-hidden">
        <div class="border-b border-slate-100 p-4">
            <form>
                <div class="relative max-w-md">
                    <input name="search" value="{{ request('search') }}" placeholder="Rechercher un article…" class="field !mt-0 pl-10">
                    <svg class="absolute left-3 top-3 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="m21 21-4.3-4.3M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                </div>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Titre</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Catégorie</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Statut</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-5 py-4 text-sm text-slate-700"><p class="font-bold">{{ $item->title }}</p><p class="text-xs text-slate-400">{{ $item->excerpt }}</p></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->category?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-sm">
                                <x-ui.status :value="$item->published_at && $item->published_at->isPast() ? 'published' : 'draft'" />
                            </td>
                            <td class="px-5 py-4 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.articles.edit', $item) }}" class="btn-secondary !px-3 !py-2">Éditer</a>
                                    <form action="{{ route('admin.articles.destroy', $item) }}" method="POST" onsubmit="return confirm('Supprimer cet article ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger !px-3 !py-2">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-slate-500">Aucun article enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>
