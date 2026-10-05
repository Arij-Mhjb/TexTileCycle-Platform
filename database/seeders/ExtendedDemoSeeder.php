<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ExtendedDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin=User::updateOrCreate(['email'=>'admin@textilecycle.test'],['name'=>'Administrateur TexTileCycle','role'=>'admin','password'=>Hash::make('password'),'email_verified_at'=>now()]);
        $brandUser=User::updateOrCreate(['email'=>'brand@textilecycle.test'],['name'=>'Responsable EcoFashion','role'=>'brand','password'=>Hash::make('password'),'email_verified_at'=>now()]);
        User::updateOrCreate(['email'=>'consumer@textilecycle.test'],['name'=>'Consommateur Démo','role'=>'consumer','password'=>Hash::make('password'),'email_verified_at'=>now()]);
        $brand=Brand::updateOrCreate(['user_id'=>$brandUser->id],[
            'name'=>'EcoFashion','slug'=>'ecofashion','description'=>'Marque de démonstration engagée dans la traçabilité et la circularité textile.',
            'website'=>'https://example.com','is_active'=>true,
        ]);
        Product::orderBy('id')->get()->each(function(Product $product,$index) use($brand){
            $product->update([
                'brand_id'=>$index<4?$brand->id:$product->brand_id,
                'slug'=>$product->slug ?: Str::slug($product->name).'-'.$product->id,
                'barcode'=>$product->barcode ?: '619000'.str_pad((string)$product->id,7,'0',STR_PAD_LEFT),
                'qr_code'=>$product->qr_code ?: 'TEXTILECYCLE:PRODUCT:'.$product->id,
                'origin'=>$product->origin ?: 'Tunisie',
                'recyclability_score'=>$product->recyclability_score ?? $product->circularity_score,
                'recycling_instructions'=>$product->recycling_instructions ?: 'Rapporter le produit dans un point de collecte adapté après réemploi ou réparation.',
                'is_active'=>$product->status==='published',
            ]);
        });
        $this->call(LabelSeeder::class);
    }
}
