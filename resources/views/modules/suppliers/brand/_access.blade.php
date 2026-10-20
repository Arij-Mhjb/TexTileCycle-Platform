{{-- Accès fournisseur par invitation : lien d'activation à usage unique (72 h), le fournisseur choisit son mot de passe. --}}
@php $sent = session('supplier_invitation'); @endphp
@if($sent)
    @php
        $digits = preg_replace('/\D+/', '', (string) ($sent['phone'] ?? ''));
        $digits = str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
        $digits = strlen($digits) === 8 ? '216'.$digits : $digits; // numéro tunisien sans indicatif
    @endphp
    <section class="mb-6 rounded-2xl border-2 border-purple-300 bg-white p-6" x-data="{ copied: false }">
        <p class="text-[13px] font-semibold text-purple-700">Invitation prête</p>
        <h2 class="mt-1 text-lg font-black text-slate-900">Envoyez ce lien d’activation à {{ $sent['supplier'] }}</h2>
        <p class="mt-1 text-sm text-slate-600">Il choisira lui-même son mot de passe : vous ne le verrez jamais. Le lien ne marche qu’une fois et expire le {{ $sent['expires_at'] }}.</p>
        <div class="mt-4 flex items-center gap-2 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
            <code class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ $sent['url'] }}</code>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            @if($digits)
                <a href="https://wa.me/{{ $digits }}?text={{ rawurlencode($sent['message']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-[#1fa855] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#178a45]">Envoyer par WhatsApp</a>
            @endif
            <button type="button" class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50"
                    @click="navigator.clipboard.writeText(@js($sent['message'])); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-text="copied ? 'Message copié' : 'Copier le message'"></span>
            </button>
        </div>
        @if($sent['sms'])
            <p class="mt-4 text-sm {{ $sent['sms']['status'] === 'failed' ? 'text-rose-700' : 'text-emerald-700' }}">
                {{ $sent['sms']['status'] === 'failed' ? 'SMS non envoyé : '.$sent['sms']['error'] : 'Lien envoyé aussi par SMS au '.$sent['sms']['to'].'.' }}
            </p>
        @endif
    </section>
@endif

@php $invitation = $supplier->latestInvitation; @endphp
<section class="shadow-[0_1px_2px_rgba(15,23,42,.05)] mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-purple-100 bg-white p-5">
    <div>
        <p class="text-[13px] font-semibold text-purple-600">Accès fournisseur</p>
        @if($supplier->user && ! $supplier->user->must_change_password)
            <p class="mt-1 font-black">Compte actif · {{ $supplier->user->email }}</p>
            <p class="text-xs text-slate-500">{{ $invitation?->used_at ? 'Activé le '.$invitation->used_at->format('d/m/Y').'. ' : '' }}Il accepte vos commandes et déclare ses expéditions depuis son espace.</p>
        @elseif($supplier->user)
            <p class="mt-1 font-black">Invitation envoyée · {{ $supplier->user->email }}</p>
            <p class="text-xs text-slate-500">{{ $invitation ? $invitation->statusLabel() : 'En attente d’activation' }}.</p>
        @else
            <p class="mt-1 font-black">Pas encore d’accès</p>
            <p class="text-xs text-slate-500">Invitez le fournisseur : il activera son compte avec un lien et choisira son mot de passe.</p>
        @endif
    </div>
    @unless($supplier->isSuspended())
        <form method="POST" action="{{ route('brand.suppliers.access', $supplier) }}" @if($supplier->user) onsubmit="return confirm('Envoyer une nouvelle invitation ? Le lien précédent ne fonctionnera plus.')" @endif>
            @csrf
            <x-module.button module="suppliers" :variant="$supplier->user ? 'soft' : 'solid'">{{ $supplier->user ? 'Nouvelle invitation' : 'Inviter le fournisseur' }}</x-module.button>
        </form>
    @endunless
</section>
