<x-app-layout title="Ateliers de réparation">
    <x-ui.page-heading title="Ateliers de réparation" description="Gérez les ateliers liés aux partenaires de réparation." :action="route('admin.repair-workshops.create')" action-label="Nouvel atelier"/>
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Nom</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Partenaire</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Ville</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Capacité</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-5 py-4 text-sm text-slate-700"><p class="font-bold">{{ $item->name }}</p><p class="text-xs text-slate-400">{{ $item->email ?? 'Sans email' }}</p></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->partner?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->city }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $item->capacity_per_week ?? 0 }} / sem.</td>
                            <td class="px-5 py-4 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.repair-workshops.show', $item) }}" class="btn-secondary !px-3 !py-2">Voir</a>
                                    <a href="{{ route('admin.repair-workshops.edit', $item) }}" class="btn-secondary !px-3 !py-2">Éditer</a>
                                    <form action="{{ route('admin.repair-workshops.destroy', $item) }}" method="POST" onsubmit="return confirm('Supprimer cet atelier ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger !px-3 !py-2">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Aucun atelier enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>
</x-app-layout>
