<?php
namespace App\Http\Controllers;
use App\Services\ProductCompletenessService;use Illuminate\Http\Request;use Illuminate\View\View;
class BrandDashboardController extends Controller {
 public function __invoke(Request $request): View {
  $brand=$request->user()->brand()->withCount('products')->firstOrFail();
  $products=$brand->products()->with('materials')->get();foreach($products as $product)app(ProductCompletenessService::class)->refresh($product);
  $assessments=$request->user()->assessments()->with('label','evidences')->latest()->get();
  $actions=collect();
  foreach($products as $product){
   if($product->completion_score<100)$actions->push(['level'=>'warning','message'=>"{$product->name} est complété à {$product->completion_score} %.",'url'=>route('brand.products.edit',$product)]);
   if($product->status==='changes_requested')$actions->push(['level'=>'danger','message'=>"Corrections demandées pour {$product->name}.",'url'=>route('brand.products.edit',$product)]);
  }
  foreach($assessments as $assessment)if($assessment->label&&$assessment->evidences->where('status','rejected')->isNotEmpty())$actions->push(['level'=>'danger','message'=>"Une preuve du dossier {$assessment->label->name} est rejetée.",'url'=>route('brand.labels.show',$assessment->label)]);
  return view('brand.dashboard',[
   'brand'=>$brand,'assessments'=>$assessments,'products'=>$products,
   'publishedProducts'=>$products->where('status','published')->count(),'reviewProducts'=>$products->where('status','review')->count(),
   'draftProducts'=>$products->whereIn('status',['draft','changes_requested'])->count(),'averageCompletion'=>(int)round($products->avg('completion_score')??0),
   'verifiedEvidence'=>$assessments->sum(fn($a)=>$a->evidences->where('status','approved')->count()),
   'pendingEvidence'=>$assessments->sum(fn($a)=>$a->evidences->where('status','pending')->count()),
   'actions'=>$actions->take(8),'activities'=>$brand->activities()->with('user')->limit(10)->get(),
   'notifications'=>$request->user()->brandNotifications()->whereNull('read_at')->latest()->limit(6)->get(),
  ]);
 }
}
