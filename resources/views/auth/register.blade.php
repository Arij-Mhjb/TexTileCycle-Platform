<x-guest-layout><form method="POST" action="{{ route('register') }}" x-data="{role:'{{ old('role','consumer') }}'}">@csrf
<h1 class="mb-6 text-2xl font-black">Créer un compte</h1>
<div><x-input-label for="name" value="Nom"/><x-text-input id="name" class="mt-1 block w-full" name="name" :value="old('name')" required autofocus/><x-input-error :messages="$errors->get('name')" class="mt-2"/></div>
<div class="mt-4"><x-input-label for="email" value="E-mail"/><x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required/><x-input-error :messages="$errors->get('email')" class="mt-2"/></div>
<div class="mt-4"><x-input-label for="role" value="Type de compte"/><select id="role" name="role" x-model="role" class="mt-1 block w-full rounded-md border-gray-300"><option value="consumer">Consommateur</option><option value="brand">Marque</option></select><x-input-error :messages="$errors->get('role')" class="mt-2"/></div>
<div class="mt-4" x-show="role==='brand'" x-cloak><x-input-label for="brand_name" value="Nom de la marque"/><x-text-input id="brand_name" class="mt-1 block w-full" name="brand_name" :value="old('brand_name')"/><x-input-error :messages="$errors->get('brand_name')" class="mt-2"/></div>
<div class="mt-4"><x-input-label for="password" value="Mot de passe"/><x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password"/><x-input-error :messages="$errors->get('password')" class="mt-2"/></div>
<div class="mt-4"><x-input-label for="password_confirmation" value="Confirmer le mot de passe"/><x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required/></div>
<div class="mt-6 flex items-center justify-between"><a class="text-sm underline" href="{{ route('login') }}">Déjà inscrit ?</a><x-primary-button>S’inscrire</x-primary-button></div>
</form></x-guest-layout>
