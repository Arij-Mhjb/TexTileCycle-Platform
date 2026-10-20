<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\ImpactMetric;
use App\Models\MarketplaceListing;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductImpactReport;
use App\Models\ProductReturn;
use App\Models\ProductSupplierLink;
use App\Models\RepairOrder;
use App\Models\ResaleOffer;
use App\Models\Supplier;
use App\Models\TradeInRequest;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Modules ajoutés par Ons Jaouadi :
 *  1. Fournisseurs & traçabilité des lots  (couleur indigo)
 *  2. Impact ESG / analyse environnementale (couleur sky)
 *  3. Réparation / revente / marketplace    (couleur amber)
 *
 * Les relations inverses sont déclarées ici pour ne pas modifier
 * les modèles existants de l'équipe.
 */
class CircularModulesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(base_path('routes/modules.php'));

        // Module 1 — Fournisseurs & traçabilité
        Brand::resolveRelationUsing('suppliers', fn (Brand $brand) => $brand->hasMany(Supplier::class));
        Product::resolveRelationUsing('supplierLinks', fn (Product $product) => $product->hasMany(ProductSupplierLink::class));

        // Module 2 — Impact ESG
        Product::resolveRelationUsing('impactReports', fn (Product $product) => $product->hasMany(ProductImpactReport::class));
        Brand::resolveRelationUsing('impactReports', fn (Brand $brand) => $brand->hasMany(ProductImpactReport::class));
        Material::resolveRelationUsing('impactMetrics', fn (Material $material) => $material->hasMany(ImpactMetric::class));

        // Module 3 — Réparation / marketplace
        ProductReturn::resolveRelationUsing('repairOrders', fn (ProductReturn $return) => $return->hasMany(RepairOrder::class));
        Product::resolveRelationUsing('marketplaceListings', fn (Product $product) => $product->hasMany(MarketplaceListing::class));
        Brand::resolveRelationUsing('marketplaceListings', fn (Brand $brand) => $brand->hasMany(MarketplaceListing::class));
        User::resolveRelationUsing('repairOrders', fn (User $user) => $user->hasMany(RepairOrder::class));
        User::resolveRelationUsing('tradeInRequests', fn (User $user) => $user->hasMany(TradeInRequest::class));
        User::resolveRelationUsing('resaleOffers', fn (User $user) => $user->hasMany(ResaleOffer::class));
    }
}
