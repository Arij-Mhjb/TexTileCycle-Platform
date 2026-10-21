<?php
namespace App\Http\Controllers;
use App\Models\BrandMember;
use App\Notifications\BrandMemberInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class BrandTeamController extends Controller {
 public function index(Request $r):View{return view('brand.team.index',['members'=>$r->user()->brand->members()->withCount('steps')->latest()->get()]);}
 public function store(Request $r):RedirectResponse{
  $brand=$r->user()->brand;$data=$r->validate(['name'=>'required|string|max:255','email'=>['required','email','max:255'],'department'=>'required|string|max:150','job_title'=>'nullable|string|max:150','phone'=>['nullable','string','max:40','regex:/^[0-9+() .-]+$/']]);
  $existing=$brand->members()->where('email',$data['email'])->first();
  if($existing && $existing->status==='active')return back()->withErrors(['email'=>'Ce collaborateur fait déjà partie de l’équipe.'])->withInput();
  $token=Str::random(64);$member=$existing ?: $brand->members()->make();$member->fill($data+['status'=>'invited','invitation_token_hash'=>hash('sha256',$token),'invited_at'=>now()])->save();
  Notification::route('mail',[$member->email=>$member->name])->notify(new BrandMemberInvitation($brand->name,$member->name,route('brand-member.invitation.show',$token)));
  return back()->with('success','Collaborateur invité. Un e-mail d’activation lui a été envoyé.');
 }
 public function resend(Request $r,BrandMember $member):RedirectResponse{abort_unless($member->brand_id===$r->user()->brand->id,403);$token=Str::random(64);$member->update(['invitation_token_hash'=>hash('sha256',$token),'invited_at'=>now()]);Notification::route('mail',[$member->email=>$member->name])->notify(new BrandMemberInvitation($member->brand->name,$member->name,route('brand-member.invitation.show',$token)));return back()->with('success','Invitation renvoyée.');}
}
