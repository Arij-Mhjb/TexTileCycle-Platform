<footer class="relative overflow-hidden border-t border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
    <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-emerald-400/10 blur-3xl"></div>
    <div class="relative mx-auto grid max-w-7xl gap-10 px-6 py-12 sm:px-8 lg:grid-cols-[1.25fr_.75fr_.75fr_1fr]">
        <div>
            <x-application-logo />
            <p class="mt-5 max-w-sm text-sm leading-6">La plateforme qui transforme chaque ticket, étiquette et retour en une décision utile pour prolonger la vie des textiles.</p>
            <div class="mt-5 flex flex-wrap gap-2">
                @foreach(['Tracer', 'Réparer', 'Réemployer', 'Recycler'] as $word)
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300">{{ $word }}</span>
                @endforeach
            </div>
        </div>
        <div>
            <p class="footer-title">Plateforme</p>
            <div class="mt-4 space-y-3 text-sm font-semibold">
                @auth
                    <a class="footer-link" href="{{ route('dashboard') }}">Mon espace</a>
                    <a class="footer-link" href="{{ route('scans.create') }}">Scanner un textile</a>
                    <a class="footer-link" href="{{ route('product-returns.index') }}">Mes retours</a>
                @else
                    <a class="footer-link" href="{{ route('register') }}">Créer un compte</a>
                    <a class="footer-link" href="{{ route('login') }}">Se connecter</a>
                    <a class="footer-link" href="{{ route('home') }}#solution">Notre solution</a>
                @endauth
            </div>
        </div>
        <div>
            <p class="footer-title">Impact</p>
            <div class="mt-4 space-y-3 text-sm font-semibold">
                <a class="footer-link" href="{{ route('home') }}#cycle">Cycle textile</a>
                <a class="footer-link" href="{{ route('home') }}#impact">Objectifs ODD</a>
                <a class="footer-link" href="https://sdgs.un.org/fr/goals" target="_blank" rel="noopener">Agenda 2030 ↗</a>
            </div>
        </div>
        <div>
            <p class="footer-title">Projet académique</p>
            <p class="mt-4 text-sm leading-6">Applications Web Avancées · ESPRIT 2026–2027</p>
            <p class="mt-3 text-sm"><span class="font-bold text-slate-800 dark:text-slate-200">Contact</span><br><a class="footer-link mt-1" href="mailto:sslack83@gmail.com">sslack83@gmail.com</a></p>
        </div>
    </div>
    <div class="relative border-t border-slate-200/80 dark:border-slate-800">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-5 text-[11px] sm:px-8 lg:flex-row lg:items-center lg:justify-between">
            <p>© {{ date('Y') }} TexTileCycle. Tous droits réservés.</p>
            <p class="max-w-3xl lg:text-right">Photos : Julia M Cameron, Burcu, Adrinil Dennis, NEOSiAM et Yeşim Çolak / Pexels · Icônes ODD : Nations Unies. Le contenu de cette plateforme n’est pas approuvé par l’ONU et ne reflète pas ses opinions.</p>
        </div>
    </div>
</footer>
