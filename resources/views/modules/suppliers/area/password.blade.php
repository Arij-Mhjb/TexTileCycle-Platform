<x-module.supplier-layout title="Mot de passe">
    <div class="mx-auto max-w-lg">
        <x-module.hero module="suppliers" eyebrow="Espace fournisseur" :title="$forced ? 'Choisissez votre mot de passe' : 'Changer de mot de passe'" :description="$forced ? 'Vous vous êtes connecté avec un mot de passe provisoire. Choisissez votre mot de passe personnel : personne d’autre ne le connaîtra.' : 'Au moins 8 caractères, avec des lettres et des chiffres.'"/>
        <form method="POST" action="{{ route('supplier.password.update') }}" class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] space-y-4 rounded-3xl border border-slate-200 bg-white p-6">
            @csrf @method('PUT')
            <x-module.field name="password" type="password" label="Nouveau mot de passe" required autocomplete="new-password"/>
            <x-module.field name="password_confirmation" type="password" label="Confirmer le mot de passe" required autocomplete="new-password"/>
            <x-module.button module="suppliers" class="w-full">Enregistrer</x-module.button>
        </form>
    </div>
</x-module.supplier-layout>
