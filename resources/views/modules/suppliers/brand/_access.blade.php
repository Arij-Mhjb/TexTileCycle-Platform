{{-- Accès fournisseur : statut du compte ; les identifiants partent uniquement par SMS au fournisseur. --}}
@php $credentials = session('supplier_access'); @endphp
@if($credentials)
    @php $sms = $credentials['sms']; @endphp
    @if($sms['status'] === 'failed')
        <section class="mb-6 rounded-2xl border-2 border-rose-300 bg-rose-50 p-6">
            <p class="text-[13px] font-semibold text-rose-700">SMS non envoyé</p>
            <h2 class="mt-1 text-lg font-black text-rose-950">Les identifiants de {{ $credentials['supplier'] }} n’ont pas pu être envoyés au {{ $sms['to'] }}</h2>
            <p class="mt-2 text-sm text-rose-900">{{ $sms['error'] }} Vérifiez le numéro (Modifier), puis générez un nouveau mot de passe provisoire.</p>
        </section>
    @else
        <section class="mb-6 rounded-2xl border-2 border-emerald-300 bg-emerald-50 p-6">
            <p class="text-[13px] font-semibold text-emerald-700">📲 Identifiants envoyés par SMS</p>
            <h2 class="mt-1 text-lg font-black text-emerald-950">{{ $credentials['supplier'] }} a reçu ses identifiants au {{ $sms['to'] }}</h2>
            <p class="mt-2 text-sm text-emerald-900">Identifiant : <strong>{{ $credentials['email'] }}</strong>. Le mot de passe provisoire est envoyé uniquement au fournisseur : vous ne le voyez pas. Il choisira son propre mot de passe à la première connexion.</p>
            @if($sms['status'] === 'logged')
                <p class="mt-3 rounded-xl bg-white px-3 py-2 text-xs font-bold text-slate-500 ring-1 ring-slate-200">Mode démo (SMS_DRIVER=log) : aucun SMS réel, le message est écrit dans storage/logs/sms-demo.log sur le serveur.</p>
            @endif
        </section>
    @endif
@endif

<section class="shadow-[0_1px_2px_rgba(15,23,42,.05)] mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-purple-100 bg-white p-5">
    <div>
        <p class="text-[13px] font-semibold text-purple-600">Accès fournisseur</p>
        @if($supplier->user)
            <p class="mt-1 font-black">✓ Compte actif · {{ $supplier->user->email }}</p>
            <p class="text-xs text-slate-500">{{ $supplier->user->must_change_password ? 'En attente de première connexion (mot de passe provisoire).' : 'Le fournisseur a choisi son mot de passe.' }} Il accepte vos commandes et déclare ses expéditions depuis son espace.</p>
        @else
            <p class="mt-1 font-black">Pas encore d’accès</p>
            <p class="text-xs text-slate-500">Créez un compte au fournisseur pour qu’il accepte vos commandes et déclare ses expéditions lui-même.</p>
        @endif
    </div>
    @unless($supplier->isSuspended())
        <form method="POST" action="{{ route('brand.suppliers.access', $supplier) }}" @if($supplier->user) onsubmit="return confirm('Générer un nouveau mot de passe provisoire ? L’ancien ne fonctionnera plus.')" @endif>
            @csrf
            <x-module.button module="suppliers" :variant="$supplier->user ? 'soft' : 'solid'">{{ $supplier->user ? '🔑 Nouveau mot de passe provisoire' : '+ Créer l’accès fournisseur' }}</x-module.button>
        </form>
    @endunless
</section>

@if($supplier->relationLoaded('smsMessages') && $supplier->smsMessages->isNotEmpty())
    <section class="shadow-[0_1px_2px_rgba(15,23,42,.05)] mb-6 rounded-2xl border border-purple-100 bg-white p-5">
        <p class="text-[13px] font-semibold text-purple-600">Historique des SMS</p>
        <ul class="mt-3 divide-y divide-slate-100 text-sm">
            @foreach($supplier->smsMessages->take(5) as $sms)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span class="font-semibold">{{ $sms->to }} <span class="text-xs text-slate-400">· {{ $sms->created_at->format('d/m/Y H:i') }} · {{ $sms->driver }}</span></span>
                    <x-module.badge :tone="['sent' => 'good', 'logged' => 'neutral', 'failed' => 'bad'][$sms->status] ?? 'neutral'">{{ $sms->statusLabel() }}</x-module.badge>
                </li>
            @endforeach
        </ul>
    </section>
@endif
