{{-- Activation du compte fournisseur depuis le lien d'invitation : il choisit lui-même son mot de passe. --}}
<x-module.supplier-layout title="Activer mon accès fournisseur">
    <div class="mx-auto max-w-lg">
        @if($invitation && $invitation->isUsable())
            <section class="rounded-2xl border border-slate-200 bg-white p-7 shadow-[0_1px_2px_rgba(15,23,42,.05)] sm:p-9">
                <p class="text-sm font-semibold text-purple-700">Invitation de {{ $invitation->supplier->brand->name }}</p>
                <h1 class="mod-display mt-2 text-3xl font-extrabold leading-tight text-slate-900">Activez votre accès fournisseur</h1>
                <p class="mt-3 text-[15px] leading-7 text-slate-600">{{ $invitation->supplier->brand->name }} vous invite à gérer ses commandes sur TexTileCycle : accepter ou proposer des modifications, expédier, joindre vos certificats de provenance.</p>
                <dl class="mt-5 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Entreprise</dt><dd class="font-semibold text-slate-900">{{ $invitation->supplier->name }}</dd></div>
                    <div><dt class="text-slate-500">Votre identifiant</dt><dd class="break-all font-semibold text-slate-900">{{ $invitation->user->email }}</dd></div>
                </dl>
                <form method="POST" action="{{ route('supplier.invitation.accept', $token) }}" class="mt-6 space-y-4">
                    @csrf
                    <x-module.field name="password" type="password" label="Choisissez votre mot de passe" autocomplete="new-password" required/>
                    <x-module.field name="password_confirmation" type="password" label="Confirmez-le" autocomplete="new-password" required/>
                    <p class="text-xs text-slate-500">8 caractères minimum, avec des lettres et des chiffres. Personne d’autre, pas même {{ $invitation->supplier->brand->name }}, ne connaîtra ce mot de passe.</p>
                    <x-module.button module="suppliers" class="w-full">Activer mon accès</x-module.button>
                </form>
                <p class="mt-5 text-xs text-slate-400">Lien personnel à usage unique, valable jusqu’au {{ $invitation->expires_at->format('d/m/Y à H:i') }}.</p>
            </section>
        @else
            <section class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-[0_1px_2px_rgba(15,23,42,.05)]">
                <h1 class="text-2xl font-black text-slate-900">Ce lien n’est plus valable</h1>
                <p class="mt-3 text-slate-600">{{ $invitation ? $invitation->unusableReason() : 'Lien d’invitation introuvable.' }}</p>
                <p class="mt-2 text-sm text-slate-500">Demandez une nouvelle invitation à votre client si besoin.</p>
                <a href="{{ route('login') }}" class="mt-6 inline-flex rounded-lg bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-purple-800">Aller à la connexion</a>
            </section>
        @endif
    </div>
</x-module.supplier-layout>
