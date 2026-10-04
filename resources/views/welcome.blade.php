<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="TexTileCycle transforme les tickets et étiquettes en passeports textiles pour faciliter réparation, don, reprise et recyclage.">
    <title>TexTileCycle · Circularité textile responsable</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f7faf8] text-slate-900 transition-colors duration-300 dark:bg-slate-950 dark:text-white">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-slate-950/85 backdrop-blur-2xl">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-6">
            <x-application-logo :on-dark="true" />
            <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-300 md:flex">
                <a href="#solution" class="transition hover:text-emerald-300">Solution</a>
                <a href="#cycle" class="transition hover:text-emerald-300">Cycle</a>
                <a href="#impact" class="transition hover:text-emerald-300">Impact ODD</a>
            </nav>
            <div class="flex items-center gap-2">
                <x-theme-toggle />
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary hidden sm:inline-flex">Mon espace</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-xl px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/10 sm:inline-flex">Connexion</a>
                    <a href="{{ route('register') }}" class="btn-primary">Créer un compte</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="hero-grid relative min-h-screen overflow-hidden bg-slate-950 pb-24 pt-36 text-white sm:pt-40">
            <div class="hero-orb absolute -left-32 top-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="hero-orb-delayed absolute -right-24 bottom-12 h-[30rem] w-[30rem] rounded-full bg-cyan-500/10 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-16 px-6 lg:grid-cols-[1.02fr_.98fr]">
                <div class="animate-fade-up">
                    <p class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-xs font-bold uppercase tracking-[.16em] text-emerald-300 backdrop-blur">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>Passeport numérique · Smart Recycling Assistant
                    </p>
                    <h1 class="mt-7 max-w-3xl text-5xl font-black leading-[1.03] tracking-tight sm:text-7xl">La seconde vie du textile devient <span class="gradient-text">visible.</span></h1>
                    <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">Photographiez un ticket ou une étiquette. TexTileCycle identifie le produit, explique son impact et recommande la meilleure option de réparation, don, reprise ou recyclage.</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="btn-primary group px-6 py-3.5">Commencer maintenant <span class="ml-2 transition-transform group-hover:translate-x-1">→</span></a>
                        <a href="#solution" class="rounded-xl border border-slate-700 bg-white/5 px-6 py-3.5 text-sm font-bold backdrop-blur transition hover:-translate-y-0.5 hover:border-emerald-400/50 hover:bg-white/10">Découvrir la plateforme</a>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-x-8 gap-y-4 border-t border-white/10 pt-6 text-xs font-bold uppercase tracking-wider text-slate-400">
                        <span><strong class="mr-2 text-lg text-white">4</strong> parcours circulaires</span>
                        <span><strong class="mr-2 text-lg text-white">100%</strong> traçable</span>
                        <span><strong class="mr-2 text-lg text-white">4</strong> ODD suivis</span>
                    </div>
                </div>

                <div class="relative min-h-[590px]" x-data="{ rx: 0, ry: 0 }" @mousemove="ry = (($event.offsetX / $el.offsetWidth) - .5) * 8; rx = (($event.offsetY / $el.offsetHeight) - .5) * -8" @mouseleave="rx = 0; ry = 0">
                    <div class="absolute inset-8 rounded-[3rem] bg-emerald-400/20 blur-3xl"></div>
                    <div class="hero-photo-card absolute inset-x-8 top-8 h-[500px] overflow-hidden rounded-[2.25rem] border border-white/15 shadow-2xl shadow-black/40 transition-transform duration-300 lg:inset-x-0" :style="`transform: perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg)`">
                        <img src="{{ asset('images/story/clothes-sorting.jpg') }}" alt="Une bénévole trie des vêtements destinés au réemploi" class="h-full w-full object-cover" fetchpriority="high">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/15 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-7">
                            <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-300">Communauté circulaire</p>
                            <p class="mt-2 max-w-md text-2xl font-black">Le bon textile, vers la bonne seconde vie.</p>
                        </div>
                    </div>
                    <div class="animate-float-soft absolute -left-1 top-0 rounded-2xl border border-white/15 bg-white/95 p-4 text-slate-900 shadow-2xl backdrop-blur dark:bg-slate-900/95 dark:text-white sm:left-0">
                        <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-100 text-xl">✓</span><div><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Produit reconnu</p><p class="font-black">Coton · 92%</p></div></div>
                    </div>
                    <div class="animate-float-reverse absolute -right-1 bottom-4 w-56 rounded-2xl border border-white/15 bg-slate-900/90 p-4 shadow-2xl backdrop-blur sm:-right-3">
                        <div class="flex items-center justify-between"><p class="text-xs font-bold text-slate-400">Circularité</p><p class="text-sm font-black text-emerald-300">88/100</p></div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10"><div class="impact-progress h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-400"></div></div>
                        <p class="mt-3 text-xs text-slate-300">Réparation recommandée à 1,4 km</p>
                    </div>
                </div>
            </div>
            <a href="#solution" class="absolute bottom-7 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 text-[10px] font-bold uppercase tracking-[.2em] text-slate-500 lg:flex"><span>Explorer</span><span class="scroll-dot h-8 w-px bg-gradient-to-b from-emerald-400 to-transparent"></span></a>
        </section>

        <section id="solution" class="relative mx-auto max-w-7xl px-6 py-24 sm:py-32">
            <div class="max-w-3xl reveal-on-scroll"><p class="eyebrow">Une décision simple</p><h2 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">Photographiez. Comprenez. Choisissez la bonne destination.</h2><p class="mt-5 text-lg leading-8 text-slate-500 dark:text-slate-400">Une expérience fluide qui transforme une preuve d’achat en parcours de circularité exploitable.</p></div>
            <div class="mt-14 grid gap-5 md:grid-cols-3">
                @foreach([
                    ['01','Identifier','Ticket, étiquette, code-barres ou QR code.','M3 5h18v14H3z M8 9h8M8 13h5'],
                    ['02','Évaluer','Composition, circularité, traçabilité et impact animal.','M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7l-8-4Z'],
                    ['03','Agir','Atelier, association, magasin de reprise ou centre de recyclage.','M20 7h-5V2M4 17h5v5M19 17a8 8 0 0 1-13 2M5 7A8 8 0 0 1 18 5']
                ] as $step)
                    <article class="feature-card panel group p-7 reveal-on-scroll">
                        <div class="flex items-center justify-between"><span class="text-sm font-black text-emerald-600 dark:text-emerald-400">{{ $step[0] }}</span><span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 transition duration-500 group-hover:rotate-6 group-hover:scale-110 dark:bg-emerald-950 dark:text-emerald-300"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $step[3] }}"/></svg></span></div>
                        <h3 class="mt-8 text-xl font-black">{{ $step[1] }}</h3><p class="mt-2 leading-7 text-slate-500 dark:text-slate-400">{{ $step[2] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="cycle" class="relative overflow-hidden bg-emerald-700 py-24 text-white sm:py-32">
            <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 1px 1px,white 1px,transparent 0);background-size:28px 28px"></div>
            <div class="relative mx-auto grid max-w-7xl gap-14 px-6 lg:grid-cols-2 lg:items-center">
                <div class="image-stack reveal-on-scroll">
                    <figure class="overflow-hidden rounded-[2rem] border border-white/15 shadow-2xl"><img src="{{ asset('images/story/community-sorting.jpg') }}" alt="Des bénévoles organisent des vêtements collectés" class="h-[430px] w-full object-cover transition duration-1000 hover:scale-105" loading="lazy"></figure>
                    <figure class="absolute -bottom-10 -right-3 hidden w-56 overflow-hidden rounded-3xl border-8 border-emerald-700 shadow-2xl sm:block"><img src="{{ asset('images/story/digital-sorting.jpg') }}" alt="Suivi numérique du tri textile" class="h-40 w-full object-cover" loading="lazy"></figure>
                </div>
                <div class="reveal-on-scroll"><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-200">Un cycle documenté</p><h2 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Du produit acheté au résultat de traitement.</h2><p class="mt-5 max-w-xl leading-8 text-emerald-50">La plateforme relie consommateurs, marques, ateliers, associations et recycleurs autour d’une donnée vérifiable.</p>
                    <div class="mt-8 grid grid-cols-2 gap-4">
                        @foreach([['Passeport','Composition et origine'],['Programmes','Reprise, don et réparation'],['Retours','Collecte et récompenses'],['Preuves','Évaluations et conformité']] as $feature)
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-5 backdrop-blur transition duration-300 hover:-translate-y-1 hover:bg-white/15"><p class="font-black">{{ $feature[0] }}</p><p class="mt-1 text-sm text-emerald-100">{{ $feature[1] }}</p></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="impact" class="mx-auto max-w-7xl px-6 py-24 sm:py-32">
            <div class="grid gap-10 lg:grid-cols-[.8fr_1.2fr] lg:items-end"><div class="reveal-on-scroll"><p class="eyebrow">Objectifs suivis</p><h2 class="mt-3 text-4xl font-black sm:text-5xl">Des actions reliées aux vrais symboles ODD.</h2></div><p class="max-w-2xl text-lg leading-8 text-slate-500 dark:text-slate-400">Chaque résultat environnemental est rattaché à un objectif du Programme de développement durable à l’horizon 2030.</p></div>
            <div class="mt-12 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach([
                    ['06','Eau','Consommation et réduction','clean-water.jpg'],
                    ['07','Énergie','Part renouvelable','clean-energy.jpg'],
                    ['12','Circularité','Réemploi et recyclage','responsible-production.jpg'],
                    ['13','Climat','Émissions évitées','climate-action.jpg']
                ] as $odd)
                    <a href="https://sdgs.un.org/fr/goals/goal{{ (int) $odd[0] }}" target="_blank" rel="noopener" class="odd-card group panel overflow-hidden reveal-on-scroll">
                        <div class="relative h-52 overflow-hidden">
                            <img src="{{ asset('images/impact/'.$odd[3]) }}" alt="Photographie illustrant l’ODD {{ (int) $odd[0] }} — {{ $odd[1] }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-110" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/5 to-transparent"></div>
                            <img src="{{ asset('images/odd/odd-'.$odd[0].'.png') }}" alt="" class="absolute left-4 top-4 h-16 w-16 rounded-xl shadow-xl ring-2 ring-white/80" loading="lazy">
                            <span class="absolute bottom-4 right-4 grid h-9 w-9 place-items-center rounded-full border border-white/25 bg-white/15 text-white backdrop-blur transition group-hover:-translate-y-1 group-hover:translate-x-1"><x-icon name="arrow-up" class="h-4 w-4" /></span>
                        </div>
                        <div class="p-4"><p class="font-black">{{ $odd[1] }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $odd[2] }}</p></div>
                    </a>
                @endforeach
            </div>
            <div class="mt-16 overflow-hidden rounded-[2rem] bg-slate-950 px-7 py-10 text-white shadow-2xl sm:px-12 sm:py-12 reveal-on-scroll"><div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between"><div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-300">Prêt à agir ?</p><h2 class="mt-3 text-3xl font-black sm:text-4xl">Faites de votre prochain retour une donnée d’impact.</h2></div><a href="{{ route('register') }}" class="btn-primary shrink-0 px-6 py-3.5">Créer mon passeport textile</a></div></div>
        </section>
    </main>

    <x-layout.footer />
</body>
</html>
