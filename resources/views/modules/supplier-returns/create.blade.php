@php $kg = fn ($value) => number_format((float) $value, 2, ',', ' ').' kg'; @endphp
<x-brand-layout title="Renvoyer de la matière">
    <x-module.hero module="repair" eyebrow="Seconde vie de la matière" :title="'Renvoyer le lot '.$batch->batch_code"
        :description="$kg($batch->quantity_kg).' de '.($batch->material?->name ?? 'matière').' reçus de '.$batch->supplier->name.($batch->received_at ? ' le '.$batch->received_at->format('d/m/Y') : '').'. Le fournisseur reprendra la matière et lui donnera une seconde vie.'"/>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('brand.supplier-returns.store') }}" enctype="multipart/form-data" class="shadow-[0_1px_2px_rgba(15,23,42,.05)] space-y-5 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 lg:col-span-2"
              x-data="{ reason: @js(old('reason', 'surplus')), sending: false }" @submit="if (sending) { $event.preventDefault() } sending = true">
            @csrf
            <input type="hidden" name="supplier_batch_id" value="{{ $batch->id }}">

            @if($returnable <= 0)
                <p class="rounded-xl bg-slate-100 p-4 text-sm text-slate-600 dark:bg-slate-800">Ce lot n’a plus de matière à renvoyer (déjà renvoyée, ou lot pas encore réceptionné).</p>
            @else
                <fieldset>
                    <legend class="text-sm font-bold text-slate-700 dark:text-slate-200">Motif du retour <span class="text-rose-500">*</span></legend>
                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        @foreach([
                            'surplus' => ['Surplus', 'Commandé en trop, matière saine'],
                            'defect' => ['Défaut qualité', 'Tache, trou, décoloration…'],
                            'wrong_item' => ['Erreur de livraison', 'Mauvaise matière ou coloris'],
                        ] as $value => [$title, $hint])
                            <label class="cursor-pointer rounded-xl border p-4 transition" :class="reason === '{{ $value }}' ? 'border-amber-500 bg-amber-50 ring-1 ring-amber-500 dark:bg-amber-950/30' : 'border-slate-200 hover:border-amber-300 dark:border-slate-700'">
                                <input type="radio" name="reason" value="{{ $value }}" x-model="reason" class="sr-only">
                                <span class="block font-bold">{{ $title }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $hint }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('reason')<p class="mt-1 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                </fieldset>

                <x-module.field name="quantity_kg" type="number" step="0.1" min="0.1" :max="$returnable" :value="old('quantity_kg', $returnable)"
                    :label="'Quantité renvoyée (kg) — maximum '.$kg($returnable)" required/>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Description <span x-show="reason === 'defect'" class="text-rose-500">*</span></span>
                    <textarea name="description" rows="3" class="field" placeholder="Ex. taches d’huile sur deux rouleaux, coloris différent de l’échantillon…">{{ old('description') }}</textarea>
                    @error('description')<span class="mt-1 block text-sm font-semibold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <label class="block rounded-2xl border border-dashed border-amber-300 bg-amber-50/60 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                    <span class="text-sm font-bold text-amber-900 dark:text-amber-200">Photo de la matière <span x-show="reason === 'defect'" class="text-rose-500">* obligatoire pour un défaut</span></span>
                    <span class="mt-1 block text-xs text-slate-500">L’IA (CLIP · Hugging Face) repère les défauts visibles et indique au fournisseur si la photo confirme le motif.</span>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-amber-700">
                    @error('photo')<span class="mt-1 block text-sm font-semibold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <div class="flex flex-wrap justify-end gap-3">
                    <x-module.button module="repair" variant="soft" :href="url()->previous()">Annuler</x-module.button>
                    <x-module.button module="repair" x-bind:disabled="sending"><span x-text="sending ? 'Envoi et analyse IA…' : 'Envoyer la demande de retour'">Envoyer la demande de retour</span></x-module.button>
                </div>
            @endif
        </form>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 text-sm dark:border-amber-900 dark:bg-amber-950/20">
                <p class="font-black text-amber-900 dark:text-amber-200">Comment ça se passe ?</p>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-slate-600 dark:text-slate-300">
                    <li>Le fournisseur accepte la reprise et choisit la seconde vie : revente à une autre marque, recyclage ou surcyclage.</li>
                    <li>Vous renvoyez la matière, il confirme la réception.</li>
                    <li>Il déclare les kg valorisés avec une preuve ; TexTileCycle la vérifie.</li>
                </ol>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-[13px] font-semibold text-slate-500">Lot</p>
                <p class="mt-1 font-black">{{ $batch->batch_code }}</p>
                <p class="text-slate-500">{{ $batch->material?->name }} · {{ $batch->origin_country }}@if($batch->purchaseOrder) · commande {{ $batch->purchaseOrder->reference }}@endif</p>
            </div>
        </aside>
    </div>
</x-brand-layout>
