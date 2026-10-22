<x-app-layout title="Propositions de passeport">
    <x-ui.page-heading title="Propositions Smart Scan" description="Contrôle des données confirmées par les consommateurs avant transmission aux marques."/>
    <form class="mb-6 flex gap-3">
        <select name="status" class="rounded-xl border-slate-300">
            <option value="">Tous les statuts</option>
            @foreach(['admin_pending'=>'À contrôler','consumer_information_required'=>'Informations demandées','brand_pending'=>'Chez la marque','brand_rejected'=>'À réexaminer','ready_for_return'=>'Prêts','admin_rejected'=>'Refusés'] as $value=>$label)
                <option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white">Filtrer</button>
    </form>
    <div class="overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Scan</th><th class="p-4">Produit proposé</th><th class="p-4">Consumer</th><th class="p-4">Statut</th><th class="p-4"></th></tr></thead>
            <tbody class="divide-y">
                @forelse($proposals as $proposal)
                    <tr><td class="p-4 font-black">#{{ $proposal->scan_id }}</td><td class="p-4">{{ data_get($proposal->proposed_data,'product_name') ?: 'Non identifié' }}<p class="text-xs text-slate-400">{{ data_get($proposal->proposed_data,'brand') }}</p></td><td class="p-4">{{ $proposal->consumer->name }}</td><td class="p-4"><span class="rounded-full bg-amber-50 px-3 py-1 font-bold text-amber-800">{{ $proposal->statusLabel() }}</span></td><td class="p-4 text-right"><a class="font-black text-emerald-700" href="{{ route('admin.passport-proposals.show',$proposal) }}">Contrôler →</a></td></tr>
                @empty<tr><td colspan="5" class="p-8 text-center text-slate-500">Aucune proposition.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $proposals->links() }}</div>
</x-app-layout>
