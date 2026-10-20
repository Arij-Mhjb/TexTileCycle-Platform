{{-- Lignes d'une commande : quantité commandée / confirmée, expédiée, reçue, avec l'avancement. --}}
@php $fmt = fn (float $kg) => rtrim(rtrim(number_format($kg, 2, ',', ' '), '0'), ','); @endphp
<div class="overflow-x-auto">
    <table class="w-full min-w-[640px] text-sm">
        <thead class="text-left text-slate-500">
            <tr class="border-b border-slate-200">
                <th class="py-2.5 pr-4 font-semibold normal-case tracking-normal">Matière</th>
                <th class="py-2.5 pr-4 text-right font-semibold normal-case tracking-normal">Commandé</th>
                <th class="py-2.5 pr-4 text-right font-semibold normal-case tracking-normal">Prix / kg</th>
                <th class="py-2.5 pr-4 font-semibold normal-case tracking-normal">Expédié</th>
                <th class="py-2.5 text-right font-semibold normal-case tracking-normal">Reçu</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->lines as $line)
                @php $changed = $line->confirmed_kg !== null && abs((float) $line->confirmed_kg - (float) $line->quantity_kg) >= 0.01; @endphp
                <tr class="border-b border-slate-100 align-top last:border-0">
                    <td class="py-3 pr-4 font-semibold text-slate-900">{{ $line->material?->name ?? 'Matière supprimée' }}</td>
                    <td class="py-3 pr-4 text-right">
                        @if($changed)
                            <span class="text-slate-400 line-through">{{ $fmt((float) $line->quantity_kg) }}</span>
                            <span class="ml-1 font-bold text-purple-800">{{ $fmt((float) $line->confirmed_kg) }} kg</span>
                            <span class="block text-xs text-purple-600">{{ in_array($order->status, ['countered'], true) ? 'proposé par le fournisseur' : 'confirmé par le fournisseur' }}</span>
                        @else
                            <span class="font-semibold">{{ $fmt((float) $line->quantity_kg) }} kg</span>
                        @endif
                    </td>
                    <td class="py-3 pr-4 text-right text-slate-600">
                        {{ $line->unit_price !== null ? number_format((float) $line->unit_price, 2, ',', ' ').' '.$order->currency : '—' }}
                        @if($line->amount() !== null)<span class="block text-xs text-slate-400">{{ number_format($line->amount(), 2, ',', ' ') }} {{ $order->currency }}</span>@endif
                    </td>
                    <td class="py-3 pr-4">
                        <div class="flex items-center gap-3">
                            <div class="h-1.5 w-28 overflow-hidden rounded-full bg-slate-100" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $line->shippedPercent() }}" aria-label="Expédié">
                                <div class="h-full rounded-full {{ $line->isFullyShipped() ? 'bg-emerald-500' : 'bg-purple-500' }}" style="width: {{ $line->shippedPercent() }}%"></div>
                            </div>
                            <span class="whitespace-nowrap tabular-nums text-slate-700">{{ $fmt($line->shippedKg()) }} / {{ $fmt($line->dueKg()) }} kg</span>
                        </div>
                        <span class="mt-1 block text-xs text-slate-400">{{ $line->batches->count() }} livraison{{ $line->batches->count() > 1 ? 's' : '' }}@if(! $line->isFullyShipped() && $line->remainingKg() > 0) · reste {{ $fmt($line->remainingKg()) }} kg @endif</span>
                    </td>
                    <td class="py-3 text-right tabular-nums text-slate-700">{{ $fmt($line->receivedKg()) }} kg</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-4 text-slate-500">Commande sans ligne détaillée.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
