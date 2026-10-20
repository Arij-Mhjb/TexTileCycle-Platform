<x-app-layout title="Partenaires circulaires">
    <x-ui.page-heading title="Partenaires circulaires" description="Gérez les partenaires de réparation et de collecte textile." :action="route('admin.repair-partners.create')" action-label="Nouveau partenaire"/>
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Nom</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Ville</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Ateliers</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-5 py-4 text-sm text-slate-700"><p class="font-bold">{{ $item->name }}</p><p class="text-xs text-slate-400">{{ $item->email ?? 'Sans email' }}</p></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->type }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->city }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->workshops_count }}</td>
                            <td class="px-5 py-4 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.repair-partners.show', $item) }}" class="btn-secondary !px-3 !py-2">Voir</a>
                                    <a href="{{ route('admin.repair-partners.edit', $item) }}" class="btn-secondary !px-3 !py-2">Éditer</a>
                                    <form action="{{ route('admin.repair-partners.destroy', $item) }}" method="POST" onsubmit="return confirm('Supprimer ce partenaire ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger !px-3 !py-2">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Aucun partenaire enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>
