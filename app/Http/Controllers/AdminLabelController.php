<?php
namespace App\Http\Controllers;
use App\Models\Label; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\Support\Str; use Illuminate\View\View;
class AdminLabelController extends Controller {
 public function index(): View { return view('admin.labels.index',['labels'=>Label::withCount('steps')->latest()->paginate(20)]); }
 public function create(): View { return view('admin.labels.form',['label'=>new Label]); }
 public function store(Request $r): RedirectResponse { $d=$this->validated($r); $d['slug']=Str::slug($d['name']).'-'.Str::lower(Str::random(4)); $l=Label::create($d); $this->steps($r,$l); return to_route('admin.labels.index')->with('success','Label créé.'); }
 public function edit(Label $label): View { $label->load('steps'); return view('admin.labels.form',compact('label')); }
 public function update(Request $r,Label $label): RedirectResponse { $label->update($this->validated($r)); $this->steps($r,$label); return to_route('admin.labels.index')->with('success','Label mis à jour.'); }
 public function destroy(Label $label): RedirectResponse { $label->delete(); return back()->with('success','Label supprimé.'); }
 private function validated(Request $r): array { $d=$r->validate(['name'=>'required|string|max:255','organization'=>'nullable|string|max:255','pillar'=>'required|in:environment,animals,social,circularity','description'=>'required|string','official_url'=>'nullable|url','cost_hint'=>'nullable|string|max:255','is_active'=>'nullable|boolean','steps'=>'required|array|min:1','steps.*'=>'nullable|string|max:255']); $d['is_active']=$r->boolean('is_active'); unset($d['steps']); return $d; }
 private function steps(Request $r,Label $l): void { $l->steps()->delete(); foreach(array_filter($r->input('steps',[])) as $i=>$title)$l->steps()->create(['title'=>$title,'position'=>$i+1]); }
}
