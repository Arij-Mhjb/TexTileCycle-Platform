@php
$suggestions = [
    'Quels fournisseurs sont les plus à risque ?',
    'Quels certificats expirent bientôt ?',
    'Où en est mon objectif carbone ?',
    'Quels produits émettent le plus de CO₂ ?',
    'Combien m’a rapporté le recyclage ?',
    'Est-ce du greenwashing : « Notre veste éco-responsable est neutre en carbone »',
];
@endphp
<x-brand-layout title="Copilote IA">
    <section class="relative isolate mb-8 overflow-hidden rounded-[2rem] bg-slate-950 p-7 text-white shadow-2xl shadow-slate-900/15 sm:p-9">
        <div class="pointer-events-none absolute -right-24 -top-32 -z-10 h-80 w-80 rounded-full bg-fuchsia-500/25 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-1/4 -z-10 h-56 w-80 rounded-full bg-sky-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 right-1/4 -z-10 h-40 w-56 rounded-full bg-teal-400/20 blur-3xl"></div>
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-2xl">
                <p class="flex items-center gap-2 text-xs font-black uppercase tracking-[.2em] text-fuchsia-300"><span class="h-2 w-2 animate-pulse rounded-full bg-fuchsia-400"></span>{{ $brand->name }} · IA avec outils</p>
                <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Copilote IA</h1>
                <p class="mt-3 leading-7 text-slate-300">Posez une question en français. Le modèle choisit lui-même les outils à appeler (risques fournisseurs, certificats, impact, objectifs carbone, recyclage, greenwashing) et répond avec vos vrais chiffres.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Modèle</p>
                @if($model)
                    <p class="mt-2 font-black text-amber-200">🤗 {{ $model }}</p>
                    <p class="text-[11px] text-slate-400">Hugging Face · appels d’outils</p>
                @else
                    <p class="mt-2 font-black text-slate-200">Mode local</p>
                    <p class="max-w-[16rem] text-[11px] leading-4 text-slate-400">{{ $hfStatus['message'] }}</p>
                @endif
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]" x-data="copilot(@js(route('brand.copilot.ask')))">
        <section class="panel flex h-[640px] flex-col overflow-hidden">
            <div x-ref="feed" class="flex-1 space-y-5 overflow-y-auto p-5 sm:p-6" aria-live="polite">
                <div class="flex gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-fuchsia-600 to-sky-600 text-sm font-black text-white">IA</span>
                    <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-slate-50 px-4 py-3 text-sm leading-6 dark:bg-slate-800">
                        Bonjour ! Je connais vos fournisseurs, leurs certificats et commandes, l’impact de vos produits, vos objectifs carbone et vos demandes de recyclage. Que voulez-vous savoir ?
                    </div>
                </div>
                <template x-for="(message, index) in messages" :key="index">
                    <div :class="message.role === 'user' ? 'flex justify-end' : 'flex gap-3'">
                        <span x-show="message.role !== 'user'" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-fuchsia-600 to-sky-600 text-sm font-black text-white">IA</span>
                        <div :class="message.role === 'user' ? 'max-w-[80%] rounded-2xl rounded-tr-sm bg-slate-900 px-4 py-3 text-sm text-white dark:bg-sky-700' : 'max-w-[85%] rounded-2xl rounded-tl-sm bg-slate-50 px-4 py-3 text-sm leading-6 dark:bg-slate-800'">
                            <div x-html="message.role === 'user' ? escape(message.content) : render(message.content)"></div>
                            <template x-if="message.tools && message.tools.length">
                                <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-slate-200 pt-2 dark:border-slate-700">
                                    <span class="text-[11px] font-bold text-slate-400">Outils :</span>
                                    <template x-for="tool in message.tools" :key="tool.name + JSON.stringify(tool.args)">
                                        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700" x-text="tool.label"></span>
                                    </template>
                                    <span class="ml-auto rounded-full px-2 py-0.5 text-[11px] font-bold" :class="message.source === 'huggingface' ? 'bg-amber-100 text-amber-900' : 'bg-slate-200 text-slate-600'" x-text="message.source === 'huggingface' ? '🤗 ' + message.model : 'Mode local'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                <div x-show="loading" x-cloak class="flex gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-fuchsia-600 to-sky-600 text-sm font-black text-white">IA</span>
                    <div class="flex items-center gap-1.5 rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800">
                        <span class="h-2 w-2 animate-bounce rounded-full bg-slate-400"></span><span class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:.15s]"></span><span class="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:.3s]"></span>
                        <span class="ml-2 text-xs text-slate-500">J’interroge vos données…</span>
                    </div>
                </div>
            </div>
            <form @submit.prevent="send()" class="border-t border-slate-100 p-4 dark:border-slate-800">
                <div class="flex items-end gap-3">
                    <textarea x-model="question" @keydown.enter.prevent="if (! $event.shiftKey) send()" rows="2" maxlength="1000" class="field !mt-0 flex-1 resize-none" placeholder="Ex. Quel fournisseur dois-je relancer cette semaine ?" aria-label="Votre question"></textarea>
                    <button class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-slate-900 text-white transition hover:bg-slate-700 disabled:opacity-50 dark:bg-sky-600" :disabled="loading || ! question.trim()" aria-label="Envoyer">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </button>
                </div>
            </form>
        </section>

        <aside class="space-y-6">
            <section class="panel p-5">
                <p class="text-xs font-black uppercase tracking-wider text-fuchsia-600">Essayez</p>
                <div class="mt-3 flex flex-col gap-2">
                    @foreach($suggestions as $suggestion)
                        <button type="button" @click="question = @js($suggestion); send()" class="rounded-xl border border-slate-200 px-3 py-2.5 text-left text-sm font-semibold text-slate-700 transition hover:border-fuchsia-300 hover:bg-fuchsia-50/50 dark:border-slate-700 dark:text-slate-200">{{ $suggestion }}</button>
                    @endforeach
                </div>
            </section>
            <section class="panel p-5">
                <p class="text-xs font-black uppercase tracking-wider text-sky-600">Outils du copilote</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($toolLabels as $name => $label)
                        <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-500"></span><span><span class="block font-semibold">{{ $label }}</span><code class="text-[10px] text-slate-400">{{ $name }}()</code></span></li>
                    @endforeach
                </ul>
                <p class="mt-4 text-xs leading-5 text-slate-500">Chaque outil ne lit que les données de {{ $brand->name }}. Le modèle ne peut rien modifier : il consulte et explique.</p>
            </section>
        </aside>
    </div>

    <script>
        function copilot(endpoint) {
            return {
                question: '', messages: [], loading: false,
                escape(text) { return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); },
                render(text) {
                    const lines = this.escape(text).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').split('\n');
                    let html = '', list = false;
                    lines.forEach((line) => {
                        const item = line.match(/^\s*[-•*]\s+(.*)$/);
                        if (item) { if (! list) { html += '<ul class="my-1 list-disc space-y-1 pl-5">'; list = true; } html += '<li>' + item[1] + '</li>'; return; }
                        if (list) { html += '</ul>'; list = false; }
                        if (line.trim() === '') { html += '<div class="h-2"></div>'; return; }
                        html += line.trim().startsWith('→') ? '<p class="mt-1 font-semibold text-fuchsia-700 dark:text-fuchsia-300">' + line + '</p>' : '<p>' + line + '</p>';
                    });
                    return html + (list ? '</ul>' : '');
                },
                scroll() { this.$nextTick(() => { this.$refs.feed.scrollTop = this.$refs.feed.scrollHeight; }); },
                async send() {
                    const question = this.question.trim();
                    if (! question || this.loading) return;
                    const history = this.messages.slice(-6).map((m) => ({ role: m.role, content: m.content }));
                    this.messages.push({ role: 'user', content: question });
                    this.question = ''; this.loading = true; this.scroll();
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ question, history }),
                        });
                        if (! response.ok) throw new Error(response.status);
                        const data = await response.json();
                        this.messages.push({ role: 'assistant', content: data.answer, tools: data.tools, source: data.source, model: data.model });
                    } catch (error) {
                        this.messages.push({ role: 'assistant', content: 'Désolé, je n’ai pas pu répondre (' + error.message + '). Réessayez dans un instant.' });
                    } finally {
                        this.loading = false; this.scroll();
                    }
                },
            };
        }
    </script>
</x-brand-layout>
