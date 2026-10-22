<x-app-layout title="Réparation & seconde vie">
    <x-module.hero module="repair" title="Réparation, reconditionnement & revente" description="Après un retour, décidez si l’article peut être réparé ou revendu, confiez-le à un centre partenaire puis publiez-le sur le marketplace circulaire.">
        <x-slot:stats>
            <x-module.stat label="À diagnostiquer" :value="$counts['requested'] ?? 0"/>
            <x-module.stat label="En atelier" :value="($counts['diagnosed'] ?? 0) + ($counts['in_repair'] ?? 0)"/>
            <x-module.stat label="Reconditionnés" :value="$counts['repaired'] ?? 0"/>
            <x-module.stat label="Remis en circulation" :value="($counts['listed'] ?? 0) + ($counts['closed'] ?? 0)"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.repair.admin._tabs')

    @if($eligibleReturns->isNotEmpty())
        <section class="mb-8 rounded-2xl border border-amber-200 bg-amber-50/60 p-6 dark:border-amber-900 dark:bg-amber-950/20">
            <p class="text-[13px] font-semibold text-amber-700">Décision de seconde vie</p>
            <h2 class="mt-1 text-lg font-black">Retours en bon état à orienter</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($eligibleReturns as $return)
                    <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
                        <p class="font-black">{{ $return->product?->name ?? 'Produit' }}</p>
                        <p class="text-xs text-slate-500">{{ $return->reference }} · {{ $return->user->name }} · état {{ str($return->condition)->replace('_', ' ') }}</p>
                        <form method="POST" action="{{ route('admin.repair-orders.store', $return) }}" class="mt-3 flex gap-2">
                            @csrf
                            <button name="decision" value="resale" class="flex-1 rounded-lg bg-amber-600 px-3 py-2 text-xs font-bold text-white hover:bg-amber-500">Reconditionner & revendre</button>
                            <button name="decision" value="repair" class="flex-1 rounded-lg bg-amber-100 px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-200">Réparer</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.repair-orders.index') }}" class="rounded-full px-4 py-2 text-sm font-bold {{ ! request('status') ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">Toutes</a>
        @foreach(\App\Models\RepairOrder::STATUSES as $value => $label)
            <a href="{{ route('admin.repair-orders.index', ['status' => $value]) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ request('status') === $value ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-amber-50' }}">{{ $label }} <span class="opacity-60">{{ $counts[$value] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="panel overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-slate-50"><tr>@foreach(['Référence', 'Article', 'Consommateur', 'Décision', 'Centre', 'État', ''] as $heading)<th class="px-5 py-3 text-left text-[13px] font-bold text-slate-400">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-5 py-4 text-sm font-bold">{{ $order->reference }}<p class="text-xs font-normal text-slate-400">{{ $order->created_at->format('d/m/Y') }}</p></td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $order->product?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $order->consumer->name }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $order->decisionLabel() ?? 'À décider' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $order->repairCenter?->name ?? '—' }}</td>
                        <td class="px-5 py-4">@include('modules.repair._status', ['order' => $order])</td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('admin.repair-orders.show', $order) }}" class="rounded-lg px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-50">Traiter</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-ui.empty message="Aucune demande de réparation."/></td></tr>
                @endforelse
            </tbody>
        </table>
        @if($orders->hasPages())<div class="border-t border-slate-100 p-4">{{ $orders->links() }}</div>@endif
    </div>
</x-app-layout>
