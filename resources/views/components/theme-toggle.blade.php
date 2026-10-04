<button
    type="button"
    x-data="{ dark: document.documentElement.classList.contains('dark') }"
    @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('textilecycle-theme', dark ? 'dark' : 'light'); window.dispatchEvent(new CustomEvent('theme-changed', { detail: { dark } }))"
    class="theme-toggle group relative grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-emerald-600 dark:hover:text-emerald-300"
    :aria-label="dark ? 'Activer le thème clair' : 'Activer le thème sombre'"
    :title="dark ? 'Thème clair' : 'Thème sombre'"
>
    <span class="absolute inset-0 bg-gradient-to-br from-amber-300/0 to-emerald-400/0 transition group-hover:from-amber-300/10 group-hover:to-emerald-400/10"></span>
    <svg x-show="!dark" x-transition:enter="transition duration-300" x-transition:enter-start="rotate-90 scale-0 opacity-0" x-transition:enter-end="rotate-0 scale-100 opacity-100" class="relative h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
    <svg x-cloak x-show="dark" x-transition:enter="transition duration-300" x-transition:enter-start="-rotate-90 scale-0 opacity-0" x-transition:enter-end="rotate-0 scale-100 opacity-100" class="relative h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.4 15.4A9 9 0 0 1 8.6 3.6 9 9 0 1 0 20.4 15.4Z"/></svg>
</button>
