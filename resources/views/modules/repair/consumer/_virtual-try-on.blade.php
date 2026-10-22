<div x-data="virtualTryOn({ imageUrl: @js($listing->image_url), category: @js($listing->try_on_category) })" @open-virtual-try-on.window="openTryOn()" @keydown.escape.window="if(open) closeTryOn()">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[120] grid place-items-center bg-slate-950/90 p-3 backdrop-blur" role="dialog" aria-modal="true" aria-label="Essayage virtuel par intelligence artificielle">
        <div class="grid max-h-[96vh] w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900 lg:grid-cols-[minmax(0,1fr)_21rem]">
            <div class="relative min-h-[28rem] overflow-hidden bg-slate-950">
                <video x-ref="video" x-show="!capturedUrl && !resultUrl" playsinline muted autoplay class="absolute inset-0 h-full w-full -scale-x-100 object-contain"></video>
                <img x-show="capturedUrl && !resultUrl" :src="capturedUrl" alt="Photo capturée pour l’essayage" class="absolute inset-0 h-full w-full object-contain">
                <img x-show="resultUrl" :src="resultUrl" alt="Résultat de l’essayage virtuel" class="absolute inset-0 h-full w-full object-contain">

                <div x-show="running && !capturedUrl" class="absolute left-4 top-4 flex items-center gap-2 rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-black text-white shadow-lg backdrop-blur"><span class="h-2.5 w-2.5 animate-pulse rounded-full bg-rose-500"></span> CAMÉRA EN DIRECT</div>
                <div x-show="loading || generating" class="absolute inset-0 z-20 grid place-items-center bg-slate-950/55 p-6 text-center text-white backdrop-blur-[2px]"><div><div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-white/20 border-t-violet-400"></div><p class="mt-4 font-black" x-text="status"></p><p x-show="generating" class="mt-2 text-sm text-slate-200">Le service ZeroGPU peut avoir une file d’attente.</p></div></div>
                <div x-show="error" class="absolute inset-x-4 bottom-4 z-30 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-rose-600 p-4 font-bold text-white"><span x-text="error"></span><button type="button" @click="capturedUrl ? captureAndGenerate() : startCamera()" :disabled="generating" class="rounded-xl bg-white px-3 py-2 text-sm font-black text-rose-700 disabled:opacity-50">Réessayer</button></div>
            </div>

            <aside class="overflow-y-auto p-5">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-widest text-violet-600">IA générative gratuite</p><h2 class="mt-1 text-xl font-black">Essayage photo réaliste</h2></div><button type="button" @click="closeTryOn()" class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-xl" aria-label="Fermer">×</button></div>

                <div class="mt-5 rounded-2xl bg-violet-50 p-4 text-sm text-violet-900">
                    <p class="font-black" x-text="status"></p>
                    <p class="mt-2 text-xs leading-5">Cadrez votre corps, puis l’IA combine votre capture avec l’image du vêtement pour produire un résultat réaliste.</p>
                </div>

                <ol class="mt-5 space-y-3 text-sm text-slate-700 dark:text-slate-200">
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-violet-100 text-xs font-black text-violet-700">1</span><span>Placez-vous face à la caméra, bras et vêtement actuel visibles.</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-violet-100 text-xs font-black text-violet-700">2</span><span>Cliquez sur « Capturer et essayer ».</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-violet-100 text-xs font-black text-violet-700">3</span><span>Attendez la génération, puis téléchargez votre résultat.</span></li>
                </ol>

                <button x-show="!capturedUrl && !resultUrl" type="button" @click="captureAndGenerate()" :disabled="!running || generating" class="mt-6 w-full rounded-2xl bg-violet-600 px-4 py-3 font-black text-white shadow-lg shadow-violet-600/20 disabled:opacity-40">📸 Capturer et essayer avec l’IA</button>
                <button x-show="capturedUrl && !resultUrl && !generating" type="button" @click="captureAndGenerate()" class="mt-6 w-full rounded-2xl bg-violet-600 px-4 py-3 font-black text-white">✨ Relancer la génération</button>
                <button x-show="resultUrl" type="button" @click="downloadResult()" class="mt-6 w-full rounded-2xl bg-emerald-600 px-4 py-3 font-black text-white">⬇ Télécharger le résultat</button>
                <button x-show="capturedUrl || resultUrl" type="button" @click="takeAnotherPhoto()" :disabled="generating" class="mt-3 w-full rounded-2xl border border-slate-300 px-4 py-3 font-black text-slate-700 disabled:opacity-40 dark:text-slate-200">Reprendre une photo</button>

                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
                    <strong>Confidentialité :</strong> après votre clic, la capture et le fichier du vêtement sont envoyés temporairement aux Spaces publics OOTDiffusion ou IDM-VTON de Hugging Face. N’utilisez pas de photo sensible. Les services gratuits peuvent être lents ou indisponibles.
                </div>
                <p class="mt-4 text-xs leading-5 text-slate-500">Résultat indicatif généré par un moteur VTON sur ZeroGPU. La coupe réelle dépend de votre morphologie, de la matière et de la taille choisie.</p>
            </aside>
        </div>
    </div>
</div>
