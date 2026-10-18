{{-- Portail fournisseur : page publique protégée par un lien signé (aucun compte requis). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Commande {{ $order->reference }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
            <x-application-logo />
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">Espace fournisseur</span>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-6 py-8">
        <x-module.hero module="suppliers" :eyebrow="'Bon de commande · '.$order->brand->name" :title="$order->reference" :description="'Bonjour '.$order->supplier->name.' : déclarez ici votre expédition et joignez le certificat de provenance de la matière.'">
            <x-slot:stats>
                <x-module.stat label="Statut" :value="$order->statusLabel()"/>
                <x-module.stat label="Commandée le" :value="$order->ordered_at->format('d/m/Y')"/>
                <x-module.stat label="Livraison souhaitée" :value="$order->expected_at?->format('d/m/Y') ?? '—'"/>
                <x-module.stat label="Montant" :value="$order->total_amount ? number_format((float) $order->total_amount, 0, ',', ' ').' '.$order->currency : '—'"/>
            </x-slot:stats>
        </x-module.hero>

        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800"><span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-600 text-white">✓</span>{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        @if($order->batches->isNotEmpty())
            <section class="mb-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black">Expéditions déclarées</h2>
                <div class="mt-4 space-y-3">
                    @foreach($order->batches as $batch)
                        <div class="rounded-2xl border border-slate-100 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-black">{{ $batch->batch_code }} <span class="text-sm font-semibold text-slate-500">· {{ $batch->material?->name }} · {{ $batch->quantity_kg }} kg · {{ $batch->origin_country }}</span></p><x-module.badge tone="suppliers">{{ $batch->statusLabel() }}</x-module.badge></div>
                            @foreach($batch->certificates as $certificate)
                                <p class="mt-2 text-sm text-slate-600">{{ $certificate->displayName() }} — {{ $certificate->statusLabel() }} @if($certificate->ai_extraction)<x-module.ai-badge class="ml-1" :source="$certificate->ai_extraction['source'] ?? 'local'"/>@endif</p>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if($order->acceptsShipment())
            <form method="POST" action="{{ $shipUrl }}" enctype="multipart/form-data" class="rounded-3xl border border-indigo-100 bg-white p-6 shadow-sm sm:p-8" x-data="{ name: '' }">
                @csrf
                <p class="text-xs font-black uppercase tracking-wider text-indigo-600">{{ $order->batches->isEmpty() ? 'Déclarer l’expédition' : 'Déclarer une autre livraison' }}</p>
                <h2 class="mt-1 text-xl font-black">Matière expédiée</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <x-ui.select name="material_id" label="Matière" :options="$materials->pluck('name', 'id')" required/>
                    <x-module.field name="quantity_kg" type="number" step="0.1" min="0.1" label="Quantité expédiée (kg)" required/>
                    <x-module.field name="origin_country" label="Pays d’origine de la matière" :value="$order->supplier->country" required/>
                    <x-module.field name="production_date" type="date" label="Date de production"/>
                    <label class="md:col-span-2 flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-violet-300 bg-gradient-to-br from-fuchsia-50/60 to-violet-50/60 p-6 text-center">
                        <span class="text-sm font-black text-violet-800">✦ Photo du certificat de provenance *</span>
                        <span class="text-xs text-slate-500">GRS, GOTS, RWS, OEKO-TEX… L’IA lit automatiquement le type, le numéro, l’organisme et les dates.</span>
                        <span class="rounded-xl bg-white px-4 py-2 text-xs font-bold text-violet-700 shadow-sm ring-1 ring-violet-200" x-text="name || 'Choisir la photo'"></span>
                        <input type="file" name="certificate" accept="image/jpeg,image/png,image/webp" required class="sr-only" @change="name = $event.target.files[0]?.name || ''">
                    </label>
                    <div class="md:col-span-2"><x-ui.textarea name="supplier_note" label="Message pour {{ $order->brand->name }} (facultatif)" rows="2"/></div>
                </div>
                <div class="mt-6 flex justify-end"><x-module.button module="suppliers">Confirmer l’expédition</x-module.button></div>
            </form>
        @else
            <div class="rounded-3xl border border-slate-200 bg-white p-6 text-center text-sm font-semibold text-slate-600">Cette commande est {{ strtolower($order->statusLabel()) }} : aucune action n’est nécessaire.</div>
        @endif
        <p class="mt-6 text-center text-xs text-slate-400">Lien sécurisé et personnel · TexTileCycle, plateforme de traçabilité textile</p>
    </main>
</body>
</html>
