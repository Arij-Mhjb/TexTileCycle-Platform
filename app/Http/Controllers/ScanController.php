<?php

namespace App\Http\Controllers;

use App\Models\Detection;
use App\Models\Scan;
use App\Services\SmartScanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        $query=Scan::with('product')->withCount('detections');
        if(!$request->user()->isAdmin()) $query->where('user_id',$request->user()->id);
        return view('scans.index',['items'=>$query->latest('scanned_at')->paginate(12)]);
    }

    public function create(): View { return view('scans.create',['item'=>new Scan]); }

    public function store(Request $request, SmartScanService $scanner): RedirectResponse
    {
        $data=$request->validate([
            'source_type'=>'required|in:barcode,qr,manual,image',
            'value'=>'nullable|required_unless:source_type,image|string|max:500',
            'image'=>'nullable|required_if:source_type,image|image|max:5120',
        ]);
        $value=$data['value'] ?? '';
        $matches=$scanner->matches($value);
        $product=$matches->count()===1 ? $matches->first() : null;
        $path=$request->hasFile('image') ? $request->file('image')->store('scans','public') : null;
        $scan=Scan::create([
            'user_id'=>$request->user()->id,'product_id'=>$product?->id,'source_type'=>$data['source_type'],
            'image_path'=>$path,'raw_text'=>$value,'status'=>$product ? 'completed' : ($matches->count()>1 ? 'multiple_matches' : 'not_found'),
            'confidence'=>$product ? $scanner->confidence($product,$value) : null,
            'result_summary'=>$matches->count()>1 ? $matches->pluck('name')->join(', ') : null,'scanned_at'=>now(),
        ]);
        foreach($matches as $match) Detection::create([
            'scan_id'=>$scan->id,'product_id'=>$match->id,'detected_name'=>$match->name,'detected_brand'=>$match->brand,
            'detected_category'=>$match->category,'confidence'=>$scanner->confidence($match,$value),'is_confirmed'=>$product?->is($match) ?? false,
        ]);
        return to_route('scans.show',$scan);
    }

    public function show(Request $request, Scan $scan): View
    {
        $this->guard($request,$scan);
        return view('scans.show',['scan'=>$scan->load('product.materials','detections.product')]);
    }

    public function destroy(Request $request, Scan $scan): RedirectResponse
    {
        $this->guard($request,$scan); $scan->delete();
        return to_route('scans.index')->with('success','Scan supprimé.');
    }

    private function guard(Request $request, Scan $scan): void
    {
        abort_unless($request->user()->isAdmin() || $scan->user_id === $request->user()->id,403);
    }
}
