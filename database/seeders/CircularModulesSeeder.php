<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Brand;
use App\Models\ImpactCategory;
use App\Models\ImpactMetric;
use App\Models\MarketplaceListing;
use App\Models\Material;
use App\Models\MaterialOriginCertificate;
use App\Models\Product;
use App\Models\ProductImpactReport;
use App\Models\ProductReturn;
use App\Models\ProductSupplierLink;
use App\Models\PurchaseOrder;
use App\Models\RepairCenter;
use App\Models\RepairOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\TradeInRequest;
use App\Models\User;
use App\Services\ImpactCalculationService;
use App\Services\PurchaseOrderService;
use App\Services\RepairMarketplaceService;
use App\Services\SupplierTraceabilityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Données de démonstration des modules Fournisseurs, Impact ESG et Seconde vie.
 * php artisan db:seed --class=CircularModulesSeeder
 */
class CircularModulesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->orderBy('id')->first();
        $consumer = User::where('role', 'consumer')->orderBy('id')->first();
        $brand = Brand::orderBy('id')->first();

        $this->impactCategories();
        if ($brand && $admin) {
            $this->suppliers($brand, $admin);
            $this->impactReports($brand, $admin);
        }
        if ($admin && $consumer) {
            $this->secondLife($admin, $consumer);
        }
    }

    private function impactCategories(): void
    {
        foreach ([
            ['co2', 'Empreinte carbone', 'kg CO₂e', 0.40, 'Gaz à effet de serre émis sur le cycle de vie.'],
            ['water', 'Consommation d’eau', 'L', 0.25, 'Eau consommée par la culture, la teinture et la fabrication.'],
            ['energy', 'Énergie', 'kWh', 0.20, 'Énergie primaire nécessaire à la transformation.'],
            ['waste', 'Déchets', 'kg', 0.15, 'Chutes de production et fin de vie non valorisée.'],
        ] as [$code, $name, $unit, $weight, $description]) {
            ImpactCategory::updateOrCreate(['code' => $code], compact('name', 'unit', 'weight', 'description'));
        }

        $categories = ImpactCategory::pluck('id', 'code');
        $factors = [
            'Coton biologique' => [3.8, 2500, 35, 0.12],
            'Coton recyclé' => [1.2, 150, 12, 0.04],
            'Polyester recyclé' => [2.1, 40, 30, 0.05],
            'Laine mérinos' => [21.0, 1500, 60, 0.2],
            'Lin européen' => [2.2, 300, 20, 0.08],
            'Chanvre' => [1.9, 400, 18, 0.07],
            'Lyocell' => [2.8, 450, 45, 0.06],
            'Cuir bovin' => [17.0, 15000, 70, 0.4],
        ];
        foreach ($factors as $materialName => $values) {
            $material = Material::where('name', $materialName)->first();
            if (! $material) {
                continue;
            }
            foreach (['co2', 'water', 'energy', 'waste'] as $index => $code) {
                ImpactMetric::updateOrCreate(
                    ['material_id' => $material->id, 'impact_category_id' => $categories[$code]],
                    ['value_per_kg' => $values[$index], 'source' => 'Valeur indicative (démo)']
                );
            }
        }
    }

    private function suppliers(Brand $brand, User $admin): void
    {
        $traceability = app(SupplierTraceabilityService::class);
        $definitions = [
            ['Filature Méditerranée', 'Tunisie', 'Monastir', 'GRS, OEKO-TEX', [
                ['LOT-FM-2026-01', 'Polyester recyclé', 420, 'Tunisie', 'verified', [['GRS', 'GRS-2026-4411', 'Control Union', 'verified']]],
                ['LOT-FM-2026-02', 'Coton recyclé', 310, 'Tunisie', 'verified', [['GRS', 'GRS-2026-4520', 'Control Union', 'verified']]],
            ]],
            ['Wool & Co Merino', 'Nouvelle-Zélande', 'Christchurch', 'RWS', [
                ['LOT-WM-2026-07', 'Laine mérinos', 180, 'Nouvelle-Zélande', 'verified', [['RWS', 'RWS-NZ-8812', 'Textile Exchange', 'verified']]],
                ['LOT-WM-2026-09', 'Laine mérinos', 95, 'Australie', 'received', [['RWS', 'RWS-AU-1020', 'Textile Exchange', 'pending']]],
            ]],
            ['Linificio Nord', 'France', 'Lille', 'Masters of Linen', [
                ['LOT-LN-2026-03', 'Lin européen', 150, 'France', 'pending', []],
            ]],
        ];

        foreach ($definitions as [$name, $country, $city, $certifications, $batches]) {
            $supplier = Supplier::updateOrCreate(
                ['brand_id' => $brand->id, 'name' => $name],
                ['country' => $country, 'city' => $city, 'certifications' => $certifications, 'status' => 'active', 'contact_name' => 'Service qualité', 'email' => 'qualite@'.str($name)->slug().'.example']
            );
            $order = $supplier->purchaseOrders()->firstOrCreate(
                ['reference' => 'PO-DEMO-'.$supplier->id],
                ['brand_id' => $brand->id, 'status' => 'received', 'ordered_at' => now()->subMonths(3), 'expected_at' => now()->subMonths(2), 'received_at' => now()->subMonths(2), 'total_amount' => 12500 + $supplier->id * 1000]
            );

            foreach ($batches as [$code, $materialName, $kg, $origin, $status, $certificates]) {
                $batch = $supplier->batches()->updateOrCreate(['batch_code' => $code], [
                    'purchase_order_id' => $order->id,
                    'material_id' => Material::where('name', $materialName)->value('id'),
                    'quantity_kg' => $kg,
                    'origin_country' => $origin,
                    'production_date' => now()->subMonths(4),
                    'received_at' => now()->subMonths(2),
                    'status' => $status,
                    'verified_by' => $status === 'verified' ? $admin->id : null,
                    'verified_at' => $status === 'verified' ? now()->subMonth() : null,
                ]);
                $batch->forceFill(['created_at' => now()->subMonths(2)->subDays(4)])->save(); // expédié quelques jours avant la réception
                foreach ($certificates as [$type, $number, $issuer, $certificateStatus]) {
                    $batch->certificates()->updateOrCreate(['certificate_number' => $number], [
                        'type' => $type, 'issuer' => $issuer, 'issued_at' => now()->subYear(), 'expires_at' => now()->addYear(),
                        'status' => $certificateStatus, 'reviewed_by' => $certificateStatus === 'verified' ? $admin->id : null,
                        'reviewed_at' => $certificateStatus === 'verified' ? now()->subMonth() : null,
                    ]);
                }
            }
            // Lignes de la commande historique : une par matière livrée, lots rattachés.
            foreach ($order->batches()->get()->groupBy('material_id') as $materialId => $materialBatches) {
                $line = $order->lines()->updateOrCreate(['material_id' => $materialId ?: null], ['quantity_kg' => $materialBatches->sum('quantity_kg'), 'unit_price' => 12.5]);
                SupplierBatch::whereIn('id', $materialBatches->pluck('id'))->update(['purchase_order_line_id' => $line->id]);
            }
            $order->load('lines.batches');
            $order->update(['total_amount' => $order->linesAmount()]);
            $traceability->refreshComplianceScore($supplier);
        }

        $this->purchaseOrderStory($brand);

        // Fournisseurs supplémentaires générés par factories (avec lots et certificats liés).
        if ($brand->suppliers()->count() < 5) {
            Supplier::factory()->count(2)->for($brand)
                ->has(SupplierBatch::factory()->count(2)->has(MaterialOriginCertificate::factory(), 'certificates'), 'batches')
                ->create()
                ->each(fn (Supplier $supplier) => $traceability->refreshComplianceScore($supplier));
        }

        // Statut automatique : « Actif » seulement pour les fournisseurs qui ont activé leur compte.
        $brand->suppliers()->with('user')->get()->each(function (Supplier $supplier) use ($traceability) {
            $supplier->syncAccountStatus();
            $traceability->refreshComplianceScore($supplier); // score à jour, livraisons de démo comprises
        });

        // Produits ↔ lots, puis contrôle automatique de conformité.
        foreach ([
            ['Winter Jacket 02', 'LOT-FM-2026-01', 'Tissu extérieur', 60],
            ['Winter Jacket 02', 'LOT-WM-2026-07', 'Doublure isolante', 40],
            ['ReLoop Shirt', 'LOT-FM-2026-02', 'Tissu principal', 70],
            ['ReLoop Shirt', 'LOT-LN-2026-03', 'Empiècements', 30],
            ['Heritage Wool Coat', 'LOT-WM-2026-09', 'Drap de laine', 85],
        ] as [$productName, $batchCode, $component, $percentage]) {
            $product = Product::where('brand_id', $brand->id)->where('name', $productName)->first();
            $batch = SupplierBatch::where('batch_code', $batchCode)->first();
            if ($product && $batch) {
                $link = ProductSupplierLink::updateOrCreate(['product_id' => $product->id, 'supplier_batch_id' => $batch->id], compact('component', 'percentage'));
                $traceability->checkConformity($link);
            }
        }
    }

    /**
     * Histoire d'achats de la collection Printemps-Été 2027, à chaque étape du cycle :
     * une commande à laquelle le fournisseur doit répondre, une contre-proposition que la marque doit arbitrer,
     * une commande livrée en partie (dont une livraison à réceptionner) et une commande en retard.
     * Comptes fournisseurs de démonstration : fournisseur@textilecycle.test et laine@textilecycle.test (mot de passe : password).
     */
    private function purchaseOrderStory(Brand $brand): void
    {
        $orders = app(PurchaseOrderService::class);
        $brandUser = User::find($brand->user_id);
        $filature = Supplier::where('brand_id', $brand->id)->where('name', 'Filature Méditerranée')->first();
        $wool = Supplier::where('brand_id', $brand->id)->where('name', 'Wool & Co Merino')->first();
        $material = fn (string $name) => Material::where('name', $name)->value('id');
        if (! $filature || ! $wool || ! $brandUser || User::where('email', 'fournisseur@textilecycle.test')->exists()) {
            return;
        }

        $account = User::updateOrCreate(['email' => 'fournisseur@textilecycle.test'], [
            'name' => 'Sami Ben Salah', 'role' => 'supplier', 'password' => Hash::make('password'),
        ]);
        $account->forceFill(['email_verified_at' => now(), 'must_change_password' => false])->save();
        $filature->update(['user_id' => $account->id, 'contact_name' => 'Sami Ben Salah', 'email' => $account->email, 'phone' => '+216 73 000 000']);

        $woolAccount = User::updateOrCreate(['email' => 'laine@textilecycle.test'], ['name' => 'Kate Morrison', 'role' => 'supplier', 'password' => Hash::make('password')]);
        $woolAccount->forceFill(['email_verified_at' => now(), 'must_change_password' => false])->save();
        $wool->update(['user_id' => $woolAccount->id, 'contact_name' => 'Kate Morrison', 'email' => $woolAccount->email]);

        // Les commandes de démo sont datées dans le passé : leur journal l'est aussi.
        $backdateCreation = fn (PurchaseOrder $order) => $order->events()->where('type', 'created')->update(['created_at' => $order->ordered_at->copy()->setTime(9, 12), 'updated_at' => $order->ordered_at->copy()->setTime(9, 12)]);

        // 1. Commande à laquelle le fournisseur doit répondre (accepter / contre-proposer / refuser).
        $backdateCreation($orders->create($filature, ['ordered_at' => now()->subDays(2)->toDateString(), 'expected_at' => now()->addWeeks(5)->toDateString(), 'notes' => 'Collection Printemps-Été 2027 : livraison en balles de 50 kg.'], [
            ['material_id' => $material('Coton recyclé'), 'quantity_kg' => 500, 'unit_price' => 13.5],
            ['material_id' => $material('Polyester recyclé'), 'quantity_kg' => 200, 'unit_price' => 9.8],
        ], $brandUser));

        // 2. Contre-proposition du fournisseur, à arbitrer par la marque.
        $woolOrder = $orders->create($wool, ['ordered_at' => now()->subDays(6)->toDateString(), 'expected_at' => now()->addWeeks(3)->toDateString(), 'notes' => 'Mérinos pour les pulls de la capsule hiver.'], [
            ['material_id' => $material('Laine mérinos'), 'quantity_kg' => 150, 'unit_price' => 42],
        ], $brandUser);
        $backdateCreation($woolOrder);
        $counteredAt = now()->subDays(1)->setTime(16, 40);
        $woolOrder->update(['status' => 'countered', 'countered_at' => $counteredAt, 'proposed_expected_at' => now()->addWeeks(5)->toDateString(), 'supplier_note' => 'Tonte retardée par la météo : 120 kg disponibles, deux semaines plus tard.']);
        $woolOrder->lines()->first()->update(['confirmed_kg' => 120]);
        $orders->log($woolOrder, 'supplier', 'countered', 'Contre-proposition : Laine mérinos : 120 kg au lieu de 150 kg ; livraison le '.now()->addWeeks(5)->format('d/m/Y').'. « Tonte retardée par la météo : 120 kg disponibles, deux semaines plus tard. »', $woolAccount, $counteredAt);

        // 3. Commande livrée en partie : une livraison reçue, une en route ; le polyester reste à livrer.
        $partial = $orders->create($filature, ['ordered_at' => now()->subWeeks(5)->toDateString(), 'expected_at' => now()->addDays(4)->toDateString(), 'notes' => 'Réassort ReLoop Shirt.'], [
            ['material_id' => $material('Coton recyclé'), 'quantity_kg' => 400, 'unit_price' => 13.2],
            ['material_id' => $material('Polyester recyclé'), 'quantity_kg' => 150, 'unit_price' => 9.6],
        ], $brandUser);
        $backdateCreation($partial);
        $acceptedAt = now()->subWeeks(5)->addDay()->setTime(10, 5);
        $partial->update(['status' => 'accepted', 'accepted_at' => $acceptedAt]);
        $orders->log($partial, 'supplier', 'accepted', 'Commande acceptée telle quelle.', $account, $acceptedAt);
        $cotton = $partial->lines()->where('material_id', $material('Coton recyclé'))->first();
        foreach ([['LOT-FM-2027-11', 250, now()->subWeeks(2)->setTime(8, 30), now()->subWeeks(2)->addDays(3)->setTime(14, 15)], ['LOT-FM-2027-12', 150, now()->subDays(2)->setTime(8, 45), null]] as [$code, $kg, $shippedAt, $receivedAt]) {
            $batch = $filature->batches()->updateOrCreate(['batch_code' => $code], [
                'purchase_order_id' => $partial->id, 'purchase_order_line_id' => $cotton->id, 'material_id' => $cotton->material_id,
                'quantity_kg' => $kg, 'origin_country' => 'Tunisie', 'production_date' => $shippedAt->copy()->subWeek(),
                'received_at' => $receivedAt, 'status' => $receivedAt ? 'received' : 'pending',
            ]);
            $batch->forceFill(['created_at' => $shippedAt])->save();
            $batch->certificates()->updateOrCreate(['certificate_number' => 'GRS-2027-'.substr($code, -2)], ['type' => 'GRS', 'issuer' => 'Control Union', 'issued_at' => now()->subMonths(3), 'expires_at' => now()->addYear(), 'status' => 'pending']);
            $orders->log($partial, 'supplier', 'shipped', 'Livraison expédiée : lot '.$code.', '.$kg.' kg de Coton recyclé.', $account, $shippedAt);
            if ($receivedAt) {
                $orders->log($partial, 'brand', 'received', 'Livraison réceptionnée : lot '.$code.' ('.$kg.' kg).', $brandUser, $receivedAt);
            }
        }
        $partial->update(['shipped_at' => now()->subWeeks(2)->setTime(8, 30)]);
        $orders->refreshStatus($partial);

        // 4. Commande acceptée mais en retard (date dépassée, rien d'expédié).
        $late = $orders->create($wool, ['ordered_at' => now()->subWeeks(7)->toDateString(), 'expected_at' => now()->subDays(5)->toDateString(), 'notes' => 'Bonnets et écharpes.'], [
            ['material_id' => $material('Laine mérinos'), 'quantity_kg' => 60, 'unit_price' => 41],
        ], $brandUser);
        $backdateCreation($late);
        $lateAcceptedAt = now()->subWeeks(7)->addDays(2)->setTime(11, 20);
        $late->update(['status' => 'accepted', 'accepted_at' => $lateAcceptedAt]);
        $orders->log($late, 'supplier', 'accepted', 'Commande acceptée telle quelle.', $woolAccount, $lateAcceptedAt);
    }

    private function impactReports(Brand $brand, User $admin): void
    {
        if (ProductImpactReport::exists()) {
            return;
        }
        $impact = app(ImpactCalculationService::class);
        foreach (Product::where('brand_id', $brand->id)->get() as $index => $product) {
            $report = $impact->generate($product, $admin, null, 'AH 2026');
            if ($index % 2 === 0) {
                $impact->validate($report, $admin, Assessment::value('id'), 'Données fournisseurs et composition vérifiées.');
            }
        }
        foreach (Product::whereNull('brand_id')->take(3)->get() as $product) {
            $impact->generate($product, $admin, null, 'PE 2027');
        }
    }

    private function secondLife(User $admin, User $consumer): void
    {
        foreach ([
            ['Atelier Retouche Médina', 'Tunis', 'Couture, retouches, boutons et fermetures', 15],
            ['Cordonnerie Verte', 'Sousse', 'Chaussures, cuir, semelles', 8],
            ['ReFab Lab', 'Sfax', 'Reconditionnement, nettoyage, teinture', 25],
        ] as [$name, $city, $specialties, $capacity]) {
            RepairCenter::updateOrCreate(['name' => $name], ['city' => $city, 'specialties' => $specialties, 'capacity_per_week' => $capacity, 'is_active' => true, 'contact_email' => 'contact@'.str($name)->slug().'.example']);
        }
        if (RepairOrder::exists()) {
            return;
        }
        // Centres et reprises supplémentaires générés par factories.
        RepairCenter::factory()->count(2)->create();
        TradeInRequest::factory()->count(2)->for($consumer, 'consumer')->create();

        $service = app(RepairMarketplaceService::class);
        $centers = RepairCenter::pluck('id', 'name');
        $returns = ProductReturn::with('product')->whereIn('condition', ['good', 'wearable', 'minor_damage'])->orderBy('id')->take(3)->get();

        // 1) Retour reconditionné et publié sur le marketplace.
        if ($first = $returns->get(0)) {
            $order = $service->createFromReturn($first, 'resale');
            $service->diagnose($order, ['decision' => 'resale', 'repair_center_id' => $centers['ReFab Lab'], 'estimated_cost' => 18]);
            $service->startRepair($order);
            $service->refurbish($order, ['actions' => 'Nettoyage écologique, remplacement de la fermeture, contrôle des coutures.', 'grade' => 'A', 'cost' => 16, 'technician' => 'Sana B.']);
            $listing = $service->publish($order->fresh(), ['title' => ($first->product?->name ?? 'Article').' — reconditionné', 'description' => 'Comme neuf après passage en atelier partenaire.', 'price' => 89]);
            $buyer = User::where('role', 'consumer')->where('id', '!=', $first->user_id)->first();
            if ($buyer) {
                $service->makeOffer($listing, $buyer, 80, 'Je peux passer le récupérer cette semaine.');
            }
        }
        // 2) Réparation en atelier.
        if ($second = $returns->get(1)) {
            $order = $service->createFromReturn($second, 'repair');
            $service->diagnose($order, ['decision' => 'repair', 'repair_center_id' => $centers['Atelier Retouche Médina'], 'estimated_cost' => 9, 'admin_note' => 'Couture d’épaule à reprendre.']);
            $service->startRepair($order);
        }
        // 3) Demande consommateur à diagnostiquer.
        if ($third = $returns->get(2)) {
            $service->createFromReturn($third, 'resale', 'Talon usé, semelle à remplacer.');
        }

        // Annonce publiée directement par la marque.
        if ($product = Product::whereNotNull('brand_id')->where('name', 'Heritage Wool Coat')->first()) {
            MarketplaceListing::firstOrCreate(['title' => 'Heritage Wool Coat — retour boutique réparé'], [
                'product_id' => $product->id, 'brand_id' => $product->brand_id, 'condition_grade' => 'A', 'price' => 240,
                'description' => 'Invendu légèrement abîmé, remis à neuf par la marque.', 'status' => 'published', 'published_at' => now(),
            ]);
        }

        // Annonces supplémentaires de marques (factory).
        MarketplaceListing::factory()->count(2)->create();

        // Reprises.
        $products = Product::where('status', 'published')->orderBy('id')->take(2)->get();
        $pending = TradeInRequest::firstOrCreate(['reference' => 'TRD-DEMO-0001'], ['user_id' => $consumer->id, 'product_id' => $products->first()?->id, 'product_name' => $products->first() ? null : 'Veste en jean', 'condition' => 'good', 'description' => 'Portée deux fois, taille M.', 'status' => 'submitted']);
        $offered = TradeInRequest::firstOrCreate(['reference' => 'TRD-DEMO-0002'], ['user_id' => $consumer->id, 'product_name' => 'Pull en laine', 'condition' => 'minor_damage', 'description' => 'Petite maille tirée sur la manche.', 'status' => 'submitted']);
        if ($offered->status === 'submitted') {
            $service->evaluateTradeIn($offered, $admin, 35, 'Réparable en atelier : offre de 35 points.');
        }
    }
}
