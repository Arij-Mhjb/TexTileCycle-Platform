{{-- « Mon compte » du fournisseur : coordonnées, identifiant de connexion, mot de passe et clients, sur une seule page. --}}
@php
    $input = 'field';
    $err = fn (string $bag, string $field) => $errors->getBag($bag)->first($field);
@endphp
<x-module.supplier-layout title="Mon compte">
    <x-module.hero module="suppliers" eyebrow="Espace fournisseur" title="Mon compte"
        description="Vos coordonnées, votre identifiant de connexion et votre mot de passe. Vos informations sont mises à jour chez tous vos clients."/>

    @if($forced)
        <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900"><strong>Choisissez d’abord votre mot de passe personnel</strong> (section « Mot de passe » ci-dessous) pour accéder à vos commandes.</div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-2">
        {{-- Coordonnées --}}
        <form method="POST" action="{{ route('supplier.account.update') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
            @csrf @method('PUT')
            <h2 class="text-lg font-black">Mes informations</h2>
            <p class="mt-1 text-sm text-slate-500">Ce que vos clients voient sur votre fiche fournisseur.</p>
            <div class="mt-5 space-y-4">
                <x-module.field name="name" label="Nom et prénom" :value="$profile?->name ?? $user->name" required/>
                <x-module.field name="phone" label="Téléphone" :value="$profile?->phone"/>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-module.field name="country" label="Pays" :value="$profile?->country" required/>
                    <x-module.field name="city" label="Ville" :value="$profile?->city"/>
                </div>
                <x-module.field name="address" label="Adresse" :value="$profile?->address"/>
            </div>
            <div class="mt-6 flex justify-end"><x-module.button module="suppliers">Enregistrer mes informations</x-module.button></div>
        </form>

        <div class="space-y-6">
        {{-- Identifiant de connexion --}}
        <form method="POST" action="{{ route('supplier.account.email') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
            @csrf @method('PUT')
            <h2 class="text-lg font-black">Identifiant de connexion</h2>
            <p class="mt-1 text-sm text-slate-500">Actuellement : <strong class="text-slate-800">{{ $user->email }}</strong></p>
            <div class="mt-4 space-y-4">
                <label class="block"><span class="text-sm font-bold text-slate-700">Nouvel e-mail</span>
                    <input type="email" name="email" value="{{ old('email') }}" class="{{ $input }}" autocomplete="email" required>
                    @if($message = $err('email', 'email'))<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@endif
                </label>
                <label class="block"><span class="text-sm font-bold text-slate-700">Mot de passe actuel</span>
                    <input type="password" name="current_password" class="{{ $input }}" autocomplete="current-password" required>
                    @if($message = $err('email', 'current_password'))<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@endif
                </label>
            </div>
            <div class="mt-5 flex justify-end"><x-module.button module="suppliers" variant="soft">Changer l’e-mail</x-module.button></div>
        </form>

        {{-- Mot de passe --}}
        <form id="mot-de-passe" method="POST" action="{{ route('supplier.password.update') }}" class="scroll-mt-6 rounded-2xl border {{ $forced ? 'border-2 border-amber-300' : 'border-slate-200' }} bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
            @csrf @method('PUT')
            <h2 class="text-lg font-black">Mot de passe</h2>
            <p class="mt-1 text-sm text-slate-500">Au moins 8 caractères, avec des lettres et des chiffres.</p>
            <div class="mt-4 space-y-4">
                @unless($forced)
                    <label class="block"><span class="text-sm font-bold text-slate-700">Mot de passe actuel</span>
                        <input type="password" name="current_password" class="{{ $input }}" autocomplete="current-password" required>
                        @if($message = $err('password', 'current_password'))<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@endif
                    </label>
                @endunless
                <label class="block"><span class="text-sm font-bold text-slate-700">Nouveau mot de passe</span>
                    <input type="password" name="password" class="{{ $input }}" autocomplete="new-password" required>
                    @if($message = $err('password', 'password'))<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@endif
                </label>
                <label class="block"><span class="text-sm font-bold text-slate-700">Confirmer le nouveau mot de passe</span>
                    <input type="password" name="password_confirmation" class="{{ $input }}" autocomplete="new-password" required>
                </label>
            </div>
            <div class="mt-5 flex justify-end"><x-module.button module="suppliers" :variant="$forced ? 'solid' : 'soft'">Changer le mot de passe</x-module.button></div>
        </form>
        </div>
    </div>

    {{-- Clients --}}
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
        <h2 class="text-lg font-black">Mes clients</h2>
        <p class="mt-1 text-sm text-slate-500">Les marques qui vous ont invité sur TexTileCycle.</p>
        <ul class="mt-4 divide-y divide-slate-100">
            @foreach($suppliers as $supplier)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                    <span class="font-semibold text-slate-900">{{ $supplier->brand->name }}</span>
                    <span class="flex items-center gap-3 text-sm text-slate-500">
                        @if($certifications = $supplier->certificationsLabel())<span>Certifications vérifiées : {{ $certifications }}</span>@endif
                        <x-module.badge :tone="$supplier->statusTone()">{{ $supplier->statusLabel() }}</x-module.badge>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>
</x-module.supplier-layout>
