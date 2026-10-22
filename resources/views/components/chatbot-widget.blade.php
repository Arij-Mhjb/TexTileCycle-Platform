<div
    x-data="chatWidget({ askUrl: '{{ route('chatbot.ask') }}', suggestionsUrl: '{{ route('chatbot.suggestions') }}' })"
    class="fixed bottom-5 right-5 z-[90] flex flex-col items-end gap-3"
>
    <div
        x-show="open"
        x-cloak
        x-transition:enter="animate-fade-up"
        class="panel flex h-[32rem] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden shadow-2xl"
    >
        <div class="flex items-center justify-between gap-3 bg-gradient-to-br from-emerald-600 to-emerald-500 px-4 py-3.5 text-white">
            <div class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/15">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12c0 4.4-4 8-9 8-1.3 0-2.5-.2-3.6-.6L3 21l1.3-4.3C3.5 15.3 3 13.7 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z"/></svg>
                </span>
                <div>
                    <p class="text-sm font-black leading-tight">Assistant TexTileCycle</p>
                    <p class="text-xs text-emerald-50/90">Questions sur nos services</p>
                </div>
            </div>
            <button type="button" @click="open = false" class="rounded-full p-1.5 text-white/80 transition hover:bg-white/15 hover:text-white" aria-label="Fermer l'assistant">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div x-ref="messages" class="flex-1 space-y-3 overflow-y-auto p-4">
            <template x-for="(entry, index) in messages" :key="index">
                <div :class="entry.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <p
                        :class="entry.role === 'user'
                            ? 'max-w-[85%] rounded-2xl rounded-br-sm bg-slate-100 px-3.5 py-2.5 text-sm text-slate-800 dark:bg-slate-800 dark:text-slate-100'
                            : 'max-w-[85%] rounded-2xl rounded-bl-sm bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100'"
                        x-text="entry.content"
                    ></p>
                </div>
            </template>
            <div x-show="loading" class="flex justify-start">
                <p class="rounded-2xl rounded-bl-sm bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200">…</p>
            </div>
            <p x-show="error" x-text="error" class="text-xs font-semibold text-rose-600"></p>
        </div>

        <div x-show="suggestions.length" class="flex flex-wrap gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-800">
            <template x-for="suggestion in suggestions" :key="suggestion.id">
                <button type="button" @click="ask(suggestion.question)" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-950/40" x-text="suggestion.question"></button>
            </template>
        </div>

        <form @submit.prevent="send" class="flex items-center gap-2 border-t border-slate-100 p-3 dark:border-slate-800">
            <input x-model="message" type="text" maxlength="500" placeholder="Posez votre question…" class="field !mt-0 flex-1" :disabled="loading">
            <button type="submit" class="btn-primary !px-4 !py-2.5" :disabled="loading">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20 20 12 4 4l2.5 8L4 20Z"/></svg>
            </button>
        </form>
    </div>

    <button
        type="button"
        @click="open = !open"
        class="grid h-14 w-14 place-items-center rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-900/25 transition duration-300 hover:-translate-y-0.5 hover:bg-emerald-500"
        :class="!open && 'animate-pulse-ring'"
        aria-label="Ouvrir l'assistant TexTileCycle"
    >
        <svg x-show="!open" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12c0 4.4-4 8-9 8-1.3 0-2.5-.2-3.6-.6L3 21l1.3-4.3C3.5 15.3 3 13.7 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z"/></svg>
        <svg x-cloak x-show="open" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
</div>
