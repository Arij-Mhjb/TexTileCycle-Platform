<x-app-layout title="Catégories du blog">
    <x-ui.page-heading title="Catégories du blog" description="Gérez les catégories utilisées pour classer les articles." :action="route('admin.categories.create')" action-label="Nouvelle catégorie"/>
    <div class="panel overflow-hidden">
        <div class="border-b border-slate-100 p-4">
            <form>
                <div class="relative max-w-md">
                    <input name="search" value="{{ request('search') }}" placeholder="Rechercher une catégorie…" class="field !mt-0 pl-10">
                    <svg class="absolute left-3 top-3 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="m21 21-4.3-4.3M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                </div>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Nom</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Articles</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-5 py-4 text-sm text-slate-700"><p class="font-bold">{{ $item->name }}</p><p class="text-xs text-slate-400">{{ Str::limit($item->description, 80) }}</p></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->articles_count }}</td>
                            <td class="px-5 py-4 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.categories.edit', $item) }}" class="btn-secondary !px-3 !py-2">Éditer</a>
                                    <form action="{{ route('admin.categories.destroy', $item) }}" method="POST" onsubmit="return confirm('Supprimer cette catégorie ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger !px-3 !py-2">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">Aucune catégorie enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>
