<div x-data="virtualTryOn({ imageUrl: @js($listing->image_url), category: @js($listing->try_on_category) })" @open-virtual-try-on.window="openTryOn()" @keydown.escape.window="if(open) closeTryOn()">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[120] grid place-items-center bg-slate-950/90 p-3 backdrop-blur" role="dialog" aria-modal="true" aria-label="Essayage virtuel">
        <div class="grid max-h-[96vh] w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <div class="relative min-h-[28rem] overflow-hidden bg-slate-950">
                <video x-ref="video" playsinline muted class="hidden"></video>
                <canvas x-ref="canvas" class="h-full max-h-[88vh] w-full object-contain"></canvas>
                <div x-show="running && !error" class="absolute left-4 top-4 flex items-center gap-2 rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-black text-white shadow-lg backdrop-blur"><span class="h-2.5 w-2.5 animate-pulse rounded-full bg-rose-500"></span> LIVE · EFFET AR</div>
                <div x-show="loading" class="absolute inset-0 grid place-items-center bg-slate-950 text-center text-white"><div><div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-white/20 border-t-violet-400"></div><p class="mt-4 font-black">Chargement du modèle IA…</p><p class="mt-1 text-sm text-slate-400">La première ouverture peut prendre quelques secondes.</p></div></div>
                <div x-show="error" class="absolute inset-x-4 bottom-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-rose-600 p-4 font-bold text-white"><span x-text="error"></span><button type="button" @click="closeTryOn(); $nextTick(() => openTryOn())" class="rounded-xl bg-white px-3 py-2 text-sm font-black text-rose-700">Réessayer</button></div>
            </div>
            <aside class="overflow-y-auto p-5">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-widest text-violet-600">IA locale</p><h2 class="mt-1 text-xl font-black">Essayage en temps réel</h2></div><button type="button" @click="closeTryOn()" class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-xl">×</button></div>
                <div class="mt-5 rounded-2xl bg-violet-50 p-4 text-sm text-violet-900"><p class="font-black" x-text="status"></p><p class="mt-1 text-xs">La caméra reste en direct. L’IA analyse localement des frames successives ; aucune image n’est envoyée au serveur.</p></div>
                <label class="mt-5 block text-sm font-bold">Taille du vêtement <input type="range" min="0.75" max="1.35" step="0.01" x-model="scale" class="mt-2 w-full accent-violet-600"></label>
                <label class="mt-5 block text-sm font-bold">Position verticale <input type="range" min="-0.4" max="0.4" step="0.01" x-model="vertical" class="mt-2 w-full accent-violet-600"></label>
                <label class="mt-5 block text-sm font-bold">Opacité <input type="range" min="0.6" max="1" step="0.01" x-model="opacity" class="mt-2 w-full accent-violet-600"></label>
                <button type="button" @click="capture()" :disabled="!running" class="mt-6 w-full rounded-2xl bg-violet-600 px-4 py-3 font-black text-white disabled:opacity-40">📸 Capturer le résultat</button>
                <p class="mt-4 text-xs leading-5 text-slate-500">Aperçu indicatif : la coupe réelle dépend de votre morphologie, de la matière et de la taille choisie. Détection corporelle locale par MediaPipe.</p>
            </aside>
        </div>
    </div>
</div>
