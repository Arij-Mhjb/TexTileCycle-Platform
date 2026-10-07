<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl text-emerald-700">✓</div>
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Inscription marque</p>
        <h1 class="mt-3 text-2xl font-black">Votre dossier est en cours de traitement</h1>
        <p class="mt-4 text-sm leading-6 text-slate-600">Après réception du questionnaire, nous étudions vos pratiques et les démarches de labels possibles. Un administrateur vérifie les recommandations et active votre compte.</p>
        <p class="mt-4 text-sm leading-6 text-slate-600">Vous recevrez un email de prise en charge, puis un email lorsque votre compte sera prêt. Vous pourrez alors vous connecter et retrouver les labels retenus pour votre marque.</p>
        <a href="{{ route('login') }}" class="mt-6 inline-block rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">Retour à la connexion</a>
    </div>
</x-guest-layout>
