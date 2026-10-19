<x-brand-layout>
    <x-module.hero module="suppliers" :title="$supplier->exists ? 'Modifier · '.$supplier->name : 'Nouveau fournisseur'" description="Identité et coordonnées du fournisseur. Son statut et ses certifications se mettent à jour tout seuls : actif dès qu’il ouvre son compte, certifications d’après ses certificats vérifiés."/>
    @include('modules.suppliers.brand._tabs')

    @if($errors->any())
        <div role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-black">{{ $errors->count() }} champ(s) à corriger :</p>
            <ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- novalidate : la validation est faite côté serveur (validate()) et les messages s'affichent via @error --}}
    <form method="POST" novalidate action="{{ $supplier->exists ? route('brand.suppliers.update', $supplier) : route('brand.suppliers.store') }}" class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
        @csrf
        @if($supplier->exists) @method('PUT') @endif
        @if($supplier->exists)
            <p class="mb-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Statut : <strong class="text-slate-900">{{ $supplier->statusLabel() }}</strong> — mis à jour automatiquement (activation du compte, suspension depuis la fiche).</p>
        @endif
        <div class="grid gap-5 md:grid-cols-2">
            <x-module.field name="name" label="Nom et prénom" :value="$supplier->name" required maxlength="150" placeholder="Ex. Sami Ben Salah"/>
            <x-module.field name="email" type="email" label="E-mail" :value="$supplier->email"/>
            <x-module.field name="phone" label="Téléphone" :value="$supplier->phone"/>
            <x-module.field name="country" label="Pays" :value="$supplier->country" required/>
            <x-module.field name="city" label="Ville" :value="$supplier->city"/>
            <x-module.field name="address" label="Adresse" :value="$supplier->address"/>
            <div class="md:col-span-2"><x-ui.textarea name="notes" label="Notes internes" :value="$supplier->notes" rows="3"/></div>
            @unless($supplier->user_id)
                <label class="md:col-span-2 flex items-start gap-3 rounded-2xl border border-purple-200 bg-purple-50/60 p-4">
                    <input type="hidden" name="create_access" value="0">
                    <input type="checkbox" name="create_access" value="1" @checked(old('create_access', ! $supplier->exists)) class="mt-1 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                    <span><span class="block text-sm font-black text-purple-900">Créer un accès fournisseur</span><span class="text-xs text-slate-600">Le fournisseur reçoit un compte TexTileCycle (avec l’e-mail ci-dessus) pour accepter vos commandes et déclarer ses expéditions. Il reçoit un lien d’activation (à transmettre par WhatsApp ou en copiant le message) et choisit lui-même son mot de passe.</span></span>
                </label>
            @endunless
        </div>
        <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
            <a href="{{ $supplier->exists ? route('brand.suppliers.show', $supplier) : route('brand.suppliers.index') }}" class="btn-secondary">Annuler</a>
            <x-module.button module="suppliers">Enregistrer</x-module.button>
        </div>
    </form>
</x-brand-layout>
