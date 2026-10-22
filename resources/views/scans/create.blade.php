<x-role-layout title="Smart Scan">
    <div class="mx-auto max-w-4xl">
        <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">Analyse assistée et contrôlée</p>
        <h1 class="mt-2 text-3xl font-black">Photographiez votre textile</h1>
        <p class="mt-3 max-w-2xl text-slate-600">Le ticket, l’étiquette et le produit sont stockés de façon durable puis analysés temporairement par l’OCR et, si configuré, Gemini Vision. Vous confirmez toujours les informations avant le dépôt.</p>

        <form method="POST" enctype="multipart/form-data" action="{{ route('scans.store') }}" class="mt-7 space-y-7 rounded-3xl border bg-white p-7 shadow-sm">
            @csrf
            <input type="hidden" name="source_type" value="multimodal">

            <div class="grid gap-5 md:grid-cols-3">
                @foreach ([
                    ['receipt_image','1 · Ticket de caisse','Prix, date, enseigne et référence','receipt'],
                    ['label_image','2 · Étiquette matière','Composition et pourcentages','label'],
                    ['product_image','3 · Produit entier','Catégorie, couleur et état','product'],
                ] as [$name,$title,$help,$capture])
                    <label class="rounded-2xl border-2 border-dashed border-slate-200 p-5 transition hover:border-emerald-400">
                        <span class="block font-black">{{ $title }}</span>
                        <span class="mt-1 block text-xs text-slate-500">{{ $help }}</span>
                        <input type="file" name="{{ $name }}" accept="image/*" capture="environment" required class="mt-4 block w-full text-sm">
                    </label>
                @endforeach
            </div>

            <details class="rounded-2xl bg-slate-50 p-5">
                <summary class="cursor-pointer font-black">Ajouter plus de preuves</summary>
                <div class="mt-4 grid gap-5 md:grid-cols-2">
                    <label class="text-sm font-bold">Dos du produit<input type="file" name="product_back_image" accept="image/*" capture="environment" class="mt-2 block w-full"></label>
                    <label class="text-sm font-bold">Photo d’un défaut<input type="file" name="damage_image" accept="image/*" capture="environment" class="mt-2 block w-full"></label>
                </div>
            </details>

            <div>
                <label class="font-bold">Référence connue — facultatif</label>
                <input name="value" value="{{ old('value') }}" placeholder="Code-barres, SKU ou nom du produit" class="mt-2 w-full rounded-xl border-slate-300">
            </div>

            <div class="rounded-2xl bg-sky-50 p-4 text-sm text-sky-900">
                Les informations extraites sont des propositions. La matière ne devient « vérifiée » qu’après le contrôle physique TexTileCycle puis la validation finale de la marque.
            </div>

            <label class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950">
                <input type="checkbox" name="privacy_consent" value="1" required class="mt-1 rounded border-sky-300 text-emerald-600">
                <span>J’autorise TexTileCycle à conserver ces images sur son stockage média sécurisé et à les transmettre temporairement à Gemini pour proposer les champs manquants. Le palier gratuit de Gemini peut utiliser les données pour améliorer ses produits. Le ticket utilise un accès authentifié et ne sera jamais montré aux marques.</span>
            </label>

            @if ($errors->any())
                <p class="rounded-xl bg-red-50 p-4 text-red-700">{{ $errors->first() }}</p>
            @endif
            <button class="w-full rounded-2xl bg-emerald-700 px-6 py-4 font-black text-white">Analyser les photos</button>
        </form>
    </div>
</x-role-layout>

