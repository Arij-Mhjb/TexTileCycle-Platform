<?php
namespace App\Http\Controllers;
use App\Models\{Assessment, CollectionPoint, Product, ProductReturn, RecoveryProgram, Scan};
use Illuminate\View\View;
class DashboardController extends Controller {
 public function __invoke(): View { return view('dashboard', [
  'stats'=>['products'=>Product::count(),'programs'=>RecoveryProgram::where('is_active',true)->count(),'points'=>CollectionPoint::where('is_active',true)->count(),'returns'=>ProductReturn::count(),'scans'=>Scan::count(),'assessments'=>Assessment::count()],
  'recentProducts'=>Product::latest()->limit(5)->get(), 'recentReturns'=>ProductReturn::with(['product','collectionPoint'])->latest()->limit(5)->get(),
 ]); }
}
