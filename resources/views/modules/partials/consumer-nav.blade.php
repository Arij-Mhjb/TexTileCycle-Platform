{{-- Lien consommateur du module Seconde vie (amber). --}}
<a href="{{ route('consumer.marketplace.index') }}" class="rounded-xl px-3.5 py-2.5 text-sm font-bold transition {{ request()->routeIs('consumer.marketplace.*', 'consumer.repairs.*', 'consumer.trade-ins.*') ? 'bg-amber-50 text-amber-800' : 'text-amber-700 hover:bg-amber-50' }}">Seconde vie</a>
