{{-- Badge d'état d'une commande de réparation : $order --}}
<x-module.badge :tone="['closed' => 'good', 'listed' => 'good', 'repaired' => 'good', 'cancelled' => 'bad', 'requested' => 'warn'][$order->status] ?? 'repair'">{{ $order->statusLabel() }}</x-module.badge>
