<?php
namespace App\Http\Controllers;
use App\Models\Evidence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class BrandEvidenceCommentController extends Controller {
 public function store(Request $request,Evidence $evidence): RedirectResponse {
  abort_unless($evidence->assessment->user_id===$request->user()->id,403);
  $data=$request->validate(['body'=>'required|string|max:3000']);
  $evidence->comments()->create(['user_id'=>$request->user()->id,'body'=>$data['body']]);
  return back()->with('success','Commentaire ajouté.');
 }
}
