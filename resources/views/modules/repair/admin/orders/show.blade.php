<x-app-layout :title="'Réparation '.$order->reference">
    <x-module.hero module="repair" :eyebrow="'Commande '.$order->reference" :title="$order->product?->name ?? 'Article sans fiche produit'" :description="$order->issue_description">
        <x-slot:actions>@include('modules.repair._status', ['order' => $order])</x-slot:actions>
    </x-module.hero>
    @include('modules.repair.admin._tabs')

    <section class="panel mb-6 p-6">@include('modules.repair._timeline', ['order' => $order])</section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="panel space-y-3 p-6 text-sm">
            <h2 class="text-lg font-black">Informations</h2>
            @foreach([
                'Consommateur' => $order->consumer->name.' · '.$order->consumer->email,
                'Retour lié' => $order->productReturn?->reference,
                'Marque' => $order->product?->brandProfile?->name ?? $order->product?->brand,
                'Décision' => $order->decisionLabel(),
                'Centre' => $order->repairCenter ? $order->repairCenter->name.' ('.$order->repairCenter->city.')' : null,
                'Coût estimé' => $order->estimated_cost ? number_format((float) $order->estimated_cost, 2, ',', ' ').' TND' : null,
                'Coût final' => $order->final_cost ? number_format((float) $order->final_cost, 2, ',', ' ').' TND' : null,
                'Points attribués' => $order->reward_points ?: null,
                'Note' => $order->admin_note,
            ] as $label => $value)
                <div><p class="text-[13px] font-bold text-slate-400">{{ $label }}</p><p class="mt-0.5 text-slate-700">{{ $value ?: '—' }}</p></div>
            @endforeach
        </section>

        <section class="space-y-6 lg:col-span-2">
            <section class="panel p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><p class="text-[13px] font-semibold text-violet-600">Intelligence artificielle</p><h2 class="mt-1 text-lg font-black">Diagnostic IA par photo</h2><p class="text-sm text-slate-500">L’IA analyse la photo et la description pour proposer la décision, le grade, le coût de réparation et le prix de revente.</p></div>
                    <form method="POST" action="{{ route('admin.repair-orders.ai-diagnosis', $order) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="max-w-[14rem] text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-violet-50 file:px-2 file:py-1.5 file:text-xs file:font-bold file:text-violet-700" aria-label="Photo de l’article">
                        <button class="rounded-xl bg-gradient-to-r from-fuchsia-600 to-violet-600 px-4 py-2.5 text-sm font-black text-white shadow-sm">✦ {{ $order->ai_diagnosis ? 'Relancer' : 'Analyser' }}</button>
                    </form>
                </div>
                @error('photo')<p class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror
                <div class="mt-4">@include('modules.repair._ai-diagnosis', ['order' => $order])</div>
            </section>
            @if(in_array($order->status, ['requested', 'diagnosed'], true))
                <form method="POST" action="{{ route('admin.repair-orders.diagnose', $order) }}" class="panel grid gap-4 p-6 md:grid-cols-2">
                    @csrf @method('PATCH')
                    <div class="md:col-span-2"><p class="text-[13px] font-semibold text-amber-600">Étape 1</p><h2 class="mt-1 text-lg font-black">Diagnostic</h2></div>
                    <x-ui.select name="decision" label="Décision" :value="$order->decision ?? ($order->ai_diagnosis['decision'] ?? null)" :options="\App\Models\RepairOrder::DECISIONS" required/>
                    <x-ui.select name="repair_center_id" label="Centre de réparation" :value="$order->repair_center_id" :options="$centers->mapWithKeys(fn ($center) => [$center->id => $center->name.' · '.$center->city])"/>
                    <x-module.field name="estimated_cost" type="number" step="0.01" min="0" label="Coût estimé (TND)" :value="$order->estimated_cost ?? ($order->ai_diagnosis['estimated_cost'] ?? null)"/>
                    <x-module.field name="admin_note" label="Note" :value="$order->admin_note"/>
                    <div class="md:col-span-2 flex justify-end"><x-module.button module="repair">Enregistrer le diagnostic</x-module.button></div>
                </form>
            @endif

            @if($order->status === 'diagnosed' && $order->repair_center_id)
                <form method="POST" action="{{ route('admin.repair-orders.start', $order) }}" class="panel flex flex-wrap items-center justify-between gap-4 p-6">
                    @csrf
                    <div><p class="text-[13px] font-semibold text-amber-600">Étape 2</p><p class="mt-1 font-black">Envoyer l’article à {{ $order->repairCenter->name }}</p></div>
                    <x-module.button module="repair">Démarrer la réparation</x-module.button>
                </form>
            @endif

            @if(in_array($order->status, ['diagnosed', 'in_repair'], true))
                <form method="POST" action="{{ route('admin.repair-orders.refurbish', $order) }}" class="panel grid gap-4 p-6 md:grid-cols-3">
                    @csrf
                    <div class="md:col-span-3"><p class="text-[13px] font-semibold text-amber-600">Étape 3</p><h2 class="mt-1 text-lg font-black">Reconditionnement terminé</h2></div>
                    <div class="md:col-span-3"><x-ui.textarea name="actions" label="Interventions réalisées" rows="3" required/></div>
                    <x-ui.select name="grade" label="Grade obtenu" :options="\App\Models\MarketplaceListing::GRADES" :value="$order->ai_diagnosis['grade'] ?? 'B'" required/>
                    <x-module.field name="cost" type="number" step="0.01" min="0" label="Coût réel (TND)"/>
                    <x-module.field name="technician" label="Technicien"/>
                    <div class="md:col-span-3 flex justify-end"><x-module.button module="repair">Valider le reconditionnement</x-module.button></div>
                </form>
            @endif

            @if($order->status === 'repaired' && $order->decision === 'resale')
                <form method="POST" action="{{ route('admin.repair-orders.publish', $order) }}" class="panel grid gap-4 p-6 md:grid-cols-3">
                    @csrf
                    <div class="md:col-span-3"><p class="text-[13px] font-semibold text-amber-600">Étape 4</p><h2 class="mt-1 text-lg font-black">Publier sur le marketplace</h2><p class="text-sm text-slate-500">Le consommateur recevra {{ \App\Services\RepairMarketplaceService::RESALE_POINTS }} points.</p></div>
                    <div class="md:col-span-2"><x-module.field name="title" label="Titre de l’annonce" :value="($order->product?->name ?? 'Article').' — reconditionné'" required/></div>
                    <x-module.field name="price" type="number" step="0.01" min="1" label="Prix (TND){{ isset($order->ai_diagnosis['resale_price']) ? ' — suggéré par l’IA' : '' }}" :value="$order->ai_diagnosis['resale_price'] ?? null" required/>
                    <div class="md:col-span-3"><x-ui.textarea name="description" label="Description" rows="3" :value="$order->refurbishments->first()?->actions"/></div>
                    <div class="md:col-span-3 flex justify-end"><x-module.button module="repair">Publier l’annonce</x-module.button></div>
                </form>
            @endif

            @if($order->status === 'repaired' && $order->decision === 'repair')
                <form method="POST" action="{{ route('admin.repair-orders.close', $order) }}" class="panel flex flex-wrap items-center justify-between gap-4 p-6">
                    @csrf
                    <div><p class="text-[13px] font-semibold text-amber-600">Étape 4</p><p class="mt-1 font-black">Rendre l’article réparé au consommateur (+{{ \App\Services\RepairMarketplaceService::REPAIR_POINTS }} points)</p></div>
                    <x-module.button module="repair">Clôturer</x-module.button>
                </form>
            @endif

            @if($order->refurbishments->isNotEmpty())
                <section class="panel p-6">
                    <h2 class="text-lg font-black">Historique atelier</h2>
                    @foreach($order->refurbishments as $refurbishment)
                        <div class="mt-3 rounded-2xl bg-amber-50/60 p-4 text-sm dark:bg-amber-950/20"><p class="font-bold">Grade {{ $refurbishment->grade }} @if($refurbishment->technician)· {{ $refurbishment->technician }}@endif @if($refurbishment->cost)· {{ number_format((float) $refurbishment->cost, 2, ',', ' ') }} TND @endif</p><p class="mt-1 text-slate-600">{{ $refurbishment->actions }}</p></div>
                    @endforeach
                </section>
            @endif

            @if($order->listing)
                <a href="{{ route('admin.marketplace.show', $order->listing) }}" class="panel flex items-center justify-between p-6 hover:border-amber-300"><span><span class="text-[13px] font-semibold text-amber-600">Annonce</span><span class="mt-1 block font-black">{{ $order->listing->title }}</span></span><x-module.badge tone="repair">{{ $order->listing->statusLabel() }} · {{ $order->listing->offers->count() }} offre(s)</x-module.badge></a>
            @endif
        </section>
    </div>
</x-app-layout>
