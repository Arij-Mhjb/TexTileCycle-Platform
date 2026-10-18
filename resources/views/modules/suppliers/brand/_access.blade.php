{{-- Accès fournisseur : statut du compte + identifiants affichés une seule fois, à transmettre par WhatsApp ou SMS. --}}
@php $credentials = session('supplier_access'); @endphp
@if($credentials)
    @php
        $digits = preg_replace('/\D+/', '', (string) ($credentials['phone'] ?? ''));
        $digits = str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
        $digits = strlen($digits) === 8 ? '216'.$digits : $digits; // numéro tunisien sans indicatif
        $message = "Bonjour, {$credentials['brand']} vous a ouvert un accès fournisseur sur TexTileCycle pour gérer ses commandes.\n"
            ."Connexion : {$credentials['login_url']}\nIdentifiant : {$credentials['email']}\nMot de passe provisoire : {$credentials['password']}\n"
            ."Vous choisirez votre propre mot de passe à la première connexion.";
    @endphp
    <section class="mb-6 rounded-3xl border-2 border-emerald-300 bg-emerald-50 p-6" x-data="{ copied: false }">
        <p class="text-xs font-black uppercase tracking-wider text-emerald-700">Affiché une seule fois</p>
        <h2 class="mt-1 text-lg font-black text-emerald-950">Identifiants de {{ $credentials['supplier'] }}</h2>
        <dl class="mt-4 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-3"><dt class="text-xs font-bold uppercase text-slate-400">Connexion</dt><dd class="mt-1 break-all text-sm font-semibold">{{ $credentials['login_url'] }}</dd></div>
            <div class="rounded-2xl bg-white p-3"><dt class="text-xs font-bold uppercase text-slate-400">Identifiant</dt><dd class="mt-1 break-all text-sm font-semibold">{{ $credentials['email'] }}</dd></div>
            <div class="rounded-2xl bg-white p-3"><dt class="text-xs font-bold uppercase text-slate-400">Mot de passe provisoire</dt><dd class="mt-1 font-mono text-lg font-black tracking-wider">{{ $credentials['password'] }}</dd></div>
        </dl>
        @php $sms = $credentials['sms'] ?? null; @endphp
        @if($sms && $sms['status'] === 'sent')
            <p class="mt-4 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">📲 SMS envoyé automatiquement au {{ $sms['to'] }} via l’API SMS ({{ $sms['driver'] ?? 'sms' }}).</p>
        @elseif($sms && $sms['status'] === 'logged')
            <p class="mt-4 rounded-2xl bg-white px-4 py-3 text-sm font-bold text-emerald-900 ring-1 ring-emerald-200">📲 SMS préparé pour {{ $sms['to'] }} (mode démo : SMS_DRIVER=log, aucun envoi réel).</p>
        @elseif($sms)
            <p class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 ring-1 ring-rose-200">⚠️ L’envoi du SMS au {{ $sms['to'] }} a échoué : {{ $sms['error'] }} Utilisez WhatsApp ou copiez le message.</p>
        @endif
        <p class="mt-3 text-sm text-emerald-900">Il devra choisir son propre mot de passe à la première connexion. Si besoin, renvoyez-les aussi :</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @if($digits)
                <a href="https://wa.me/{{ $digits }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-black text-white shadow-sm hover:brightness-95">💬 Envoyer par WhatsApp</a>
            @else
                <p class="rounded-xl bg-white px-3 py-2 text-xs font-bold text-amber-700">Ajoutez le téléphone du fournisseur (Modifier) pour l’envoi automatique par SMS.</p>
            @endif
            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-black text-emerald-800 ring-1 ring-emerald-300"
                    @click="navigator.clipboard.writeText(@js($message)); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-text="copied ? '✓ Copié' : '📋 Copier le message'"></span>
            </button>
        </div>
    </section>
@endif

<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-indigo-100 bg-white p-5 shadow-sm">
    <div>
        <p class="text-xs font-black uppercase tracking-wider text-indigo-600">Accès fournisseur</p>
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
    <section class="mb-6 rounded-3xl border border-indigo-100 bg-white p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-wider text-indigo-600">Historique des SMS</p>
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
