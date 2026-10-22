<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-indigo-100 text-2xl">✓</div>
        <h1 class="mt-4 text-2xl font-black text-slate-900 dark:text-white">Accéder à ma tâche</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Connectez-vous avec le compte collaborateur qui a reçu l’e-mail pour ouvrir directement :</p>
        <p class="mt-2 font-bold text-indigo-700 dark:text-indigo-300">{{ $step->title }}</p>
    </div>

    <form method="POST" action="{{ $accessUrl }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Adresse professionnelle" />
            <x-text-input id="email" class="mt-1 block w-full bg-slate-100" type="email" :value="$member->email" readonly />
            <p class="mt-1 text-xs text-slate-500">Cette adresse est liée à l’affectation reçue.</p>
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-slate-600 underline hover:text-slate-900 dark:text-slate-400 dark:hover:text-white" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
            @endif
            <x-primary-button>Se connecter et ouvrir la tâche</x-primary-button>
        </div>
    </form>
</x-guest-layout>
