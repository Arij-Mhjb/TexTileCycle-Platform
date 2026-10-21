@php
$icons = [
    'order' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h5"/>',
    'ship' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
    'loop' => '<path d="M20 12a8 8 0 0 1-13.7 5.6M4 12a8 8 0 0 1 13.7-5.6"/><path d="M18 3v4h-4M6 21v-4h4"/>',
    'cert' => '<path d="M12 3 4 6v6c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
    'account' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
];
$todo = collect();
foreach ($orders->filter->awaitsSupplierAnswer() as $order) {
    $todo->push(['order', $order->brand->name, 'Nouvelle commande '.$order->reference.' : acceptez-la, refusez-la ou proposez autre chose.', 'Répondre à la commande', route('supplier.orders.show', $order), false]);
}
foreach ($orders->filter->acceptsShipment() as $order) {
    $todo->push(['ship', $order->brand->name, $order->reference.' : '.$order->shippedPercent().' % expédié'.($order->expected_at ? ', à livrer le '.$order->expected_at->format('d/m/Y') : '').'.', 'Déclarer une expédition', route('supplier.orders.show', $order), $order->isLate()]);
}
foreach ($todoLoops as $loop) {
    $todo->push(['loop', $loop->brand->name, $loop->situation('supplier'), $loop->nextAction(), route('supplier.recycling.show', $loop), false]);
}
if ($batchesWithoutCertificate > 0) {
    $todo->push(['cert', 'Certificats de provenance', $batchesWithoutCertificate.' lot(s) n’ont pas encore de certificat valable. Une simple photo suffit.', 'Ajouter un certificat', route('supplier.batches.index'), false]);
}
$tone = ['order' => 'bg-purple-700', 'ship' => 'bg-purple-700', 'loop' => 'bg-teal-700', 'cert' => 'bg-pink-600'];
$openOrders = $orders->filter(fn ($order) => $order->awaitsSupplierAnswer() || $order->acceptsShipment())->count();
$tiles = [
    ['order', 'Commandes', $openOrders, $openOrders === 1 ? 'commande en cours' : 'commandes en cours', '#commandes', 'bg-purple-50 text-purple-700'],
    ['cert', 'Lots & certificats', $batchCount, $batchCount === 1 ? 'lot livré' : 'lots livrés', route('supplier.batches.index'), 'bg-pink-50 text-pink-700'],
    ['loop', 'Recyclage', $loops->filter->isOpen()->count(), 'demande(s) de recyclage', route('supplier.recycling.index'), 'bg-teal-50 text-teal-700'],
    ['account', 'Mon compte', null, 'coordonnées et mot de passe', route('supplier.account.edit'), 'bg-slate-100 text-slate-700'],
];
@endphp
<x-module.supplier-layout title="Accueil">
    {{-- Bonjour + ce qu'il y a à faire --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
        <p class="text-sm font-semibold text-purple-600">{{ now()->locale('fr')->isoFormat('dddd D MMMM') }}</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">Bonjour {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-lg text-slate-600">
            @if($todo->isEmpty())
                Tout est à jour. Rien à faire pour le moment.
            @else
                Vous avez <strong class="text-slate-900">{{ $todo->count() }} {{ $todo->count() > 1 ? 'choses' : 'chose' }} à faire</strong>. Commencez par la première.
            @endif
        </p>

        @if($todo->isNotEmpty())
            <ol class="mt-6 space-y-3">
                @foreach($todo as $index => [$type, $who, $what, $button, $url, $late])
                    <li class="flex flex-wrap items-center gap-4 rounded-2xl border p-4 sm:flex-nowrap {{ $index === 0 ? 'border-purple-300 bg-purple-50/50' : 'border-slate-200' }}">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-white {{ $tone[$type] }}">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$type] !!}</svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-black text-slate-900">{{ $who }}@if($late)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">En retard</span>@endif</p>
                            <p class="text-sm text-slate-600">{{ $what }}</p>
                        </div>
                        <a href="{{ $url }}" class="w-full shrink-0 rounded-xl px-5 py-3 text-center text-sm font-black text-white sm:w-auto {{ $tone[$type] }} hover:opacity-90">{{ $button }} →</a>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="mt-6 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                Vous serez prévenu (cloche en haut) dès qu’une marque vous écrit.
            </div>
        @endif
    </section>

    {{-- Les 4 espaces --}}
    <nav class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Mon espace">
        @foreach($tiles as [$type, $label, $count, $hint, $url, $color])
            <a href="{{ $url }}" class="group rounded-3xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-purple-200 hover:shadow-[0_16px_30px_-22px_rgba(88,28,135,.6)]">
                <span class="grid h-12 w-12 place-items-center rounded-2xl {{ $color }}">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$type] !!}</svg>
                </span>
                <span class="mt-4 flex items-baseline gap-2">
                    <span class="text-lg font-black text-slate-900">{{ $label }}</span>
                    @if($count !== null)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-black tabular-nums text-slate-700">{{ $count }}</span>@endif
                </span>
                <span class="mt-0.5 block text-sm text-slate-500">{{ $hint }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Commandes --}}
    <section id="commandes" class="mt-10 scroll-mt-6">
        <h2 class="mb-3 text-xl font-black">Mes commandes</h2>
        <div class="space-y-3">
            @forelse($orders as $order)
                <a href="{{ route('supplier.orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-purple-200">
                    <div>
                        <p class="text-[13px] font-semibold text-purple-600">{{ $order->brand->name }}</p>
                        <p class="mt-1 text-lg font-black">{{ $order->reference }}</p>
                        <p class="text-sm text-slate-500">Commandée le {{ $order->ordered_at->format('d/m/Y') }}@if($order->expected_at) · à livrer le {{ $order->expected_at->format('d/m/Y') }}@endif @if($order->total_amount) · {{ number_format((float) $order->total_amount, 2, ',', ' ') }} {{ $order->currency }}@endif</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($order->awaitsSupplierAnswer())<span class="rounded-full bg-purple-700 px-3 py-1 text-xs font-semibold text-white">Réponse attendue</span>@endif
                        @if($order->isLate())<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">En retard</span>@endif
                        @if($order->lines->isNotEmpty() && $order->acceptsShipment())<span class="text-sm text-slate-500">{{ $order->shippedPercent() }} % expédié</span>@endif
                        <x-module.badge :tone="$order->statusTone()">{{ $order->statusLabel() }}</x-module.badge>
                        <span class="text-purple-600">→</span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-purple-200 bg-white p-10 text-center text-sm text-slate-500">Aucune commande pour le moment.</div>
            @endforelse
        </div>
    </section>
</x-module.supplier-layout>
