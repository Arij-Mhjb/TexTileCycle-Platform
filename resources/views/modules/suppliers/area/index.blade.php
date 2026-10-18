<x-module.supplier-layout title="Mes commandes">
    <x-module.hero module="suppliers" eyebrow="Espace fournisseur" :title="'Bonjour '.auth()->user()->name" description="Retrouvez les bons de commande de vos clients : acceptez-les ou refusez-les, puis déclarez vos expéditions avec le certificat de provenance.">
        <x-slot:stats>
            <x-module.stat label="À valider" :value="$orders->whereIn('status', ['ordered', 'sent'])->count()"/>
            <x-module.stat label="Acceptées" :value="$orders->where('status', 'accepted')->count()"/>
            <x-module.stat label="Expédiées" :value="$orders->where('status', 'shipped')->count()"/>
            <x-module.stat label="Clients" :value="$orders->pluck('brand_id')->unique()->count()"/>
        </x-slot:stats>
    </x-module.hero>

    <div class="space-y-4">
        @forelse($orders as $order)
            <a href="{{ route('supplier.orders.show', $order) }}" class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-slate-200 bg-white p-5 transition hover:-translate-y-1 hover:border-purple-200">
                <div>
                    <p class="text-xs font-black uppercase tracking-wider text-purple-600">{{ $order->brand->name }}</p>
                    <p class="mt-1 text-lg font-black">{{ $order->reference }}</p>
                    <p class="text-sm text-slate-500">Commandée le {{ $order->ordered_at->format('d/m/Y') }}@if($order->expected_at) · livraison souhaitée le {{ $order->expected_at->format('d/m/Y') }}@endif @if($order->total_amount) · {{ number_format((float) $order->total_amount, 2, ',', ' ') }} {{ $order->currency }}@endif</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($order->awaitsSupplierAnswer())<span class="rounded-full bg-purple-600 px-3 py-1 text-xs font-black text-white">Réponse attendue</span>@endif
                    <x-module.badge :tone="['received' => 'good', 'accepted' => 'good', 'cancelled' => 'bad', 'declined' => 'bad', 'shipped' => 'warn'][$order->status] ?? 'suppliers'">{{ $order->statusLabel() }}</x-module.badge>
                    <span class="text-purple-600">→</span>
                </div>
            </a>
        @empty
            <div class="rounded-3xl border border-dashed border-purple-200 bg-white p-10 text-center text-sm text-slate-500">Aucune commande pour le moment.</div>
        @endforelse
    </div>
</x-module.supplier-layout>
