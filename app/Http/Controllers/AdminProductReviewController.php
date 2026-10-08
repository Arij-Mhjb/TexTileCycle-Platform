<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Services\ActivityService;
use App\Services\ProductWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class AdminProductReviewController extends Controller {
 public function approve(Request $request,Product $product): RedirectResponse {
  abort_unless($product->status==='review',422);
  app(ProductWorkflowService::class)->publish($product,$request->user()->id);
  app(ActivityService::class)->log($request->user()->id,$product->brand_id,'product.published',$product);
  if($product->brandProfile)app(ActivityService::class)->notify($product->brandProfile->user_id,'product_approved','Produit publié — analyse disponible',"{$product->name} a été vérifié et publié. Vous pouvez maintenant lancer l’analyse des labels compatibles.",route('brand.product-label-recommendations.index'));
  return back()->with('success','Produit vérifié et publié.');
 }
 public function reject(Request $request,Product $product): RedirectResponse {
  $data=$request->validate(['review_comment'=>'required|string|max:2000']);
  $product->update(['status'=>'changes_requested','is_active'=>false,'review_comment'=>$data['review_comment']]);
  app(ActivityService::class)->log($request->user()->id,$product->brand_id,'product.changes_requested',$product,['comment'=>$data['review_comment']]);
  if($product->brandProfile)app(ActivityService::class)->notify($product->brandProfile->user_id,'product_rejected','Corrections demandées',"Des corrections sont demandées pour {$product->name}.",route('brand.products.edit',$product));
  return back()->with('success','Demande de correction envoyée.');
 }
}
