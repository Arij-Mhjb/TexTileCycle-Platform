<?php
namespace App\Http\Controllers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
class BrandProfileController extends Controller {
 public function edit(Request $request): View { return view('brand.profile', ['brand'=>$request->user()->brand]); }
 public function update(Request $request): RedirectResponse {
  $data=$request->validate(['name'=>'required|string|max:255','description'=>'nullable|string|max:5000','website'=>'nullable|url|max:255','country'=>'nullable|string|max:100','commitments'=>'nullable|string|max:5000','contact_email'=>'nullable|email|max:255','logo'=>'nullable|image|max:3072']);
  $brand=$request->user()->brand;
  if($request->hasFile('logo')){if($brand->logo_path)Storage::disk('public')->delete($brand->logo_path);$data['logo_path']=$request->file('logo')->store('brands','public');}
  unset($data['logo']);$brand->update($data);
  app(\App\Services\ActivityService::class)->log($request->user()->id,$brand->id,'brand.profile_updated',$brand);
  return back()->with('success','Profil de marque mis à jour.');
 }
}
