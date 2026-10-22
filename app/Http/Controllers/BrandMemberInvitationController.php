<?php
namespace App\Http\Controllers;
use App\Models\BrandMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class BrandMemberInvitationController extends Controller {
 private function member(string $token):BrandMember{return BrandMember::with('brand')->where('invitation_token_hash',hash('sha256',$token))->where('status','invited')->firstOrFail();}
 public function show(string $token):View{return view('auth.brand-member-invitation',['member'=>$this->member($token),'token'=>$token]);}
 public function store(Request $r,string $token):RedirectResponse{$member=$this->member($token);$data=$r->validate(['password'=>'required|string|min:8|confirmed']);$existing=User::where('email',$member->email)->first();if($existing&&!in_array($existing->role,['brand','brand_member'],true))return back()->withErrors(['email'=>'Cette adresse appartient à un compte incompatible avec l’espace collaborateur. Utilisez une autre adresse professionnelle.']);$user=DB::transaction(function()use($member,$data,$existing){$user=$existing?:User::create(['email'=>$member->email,'name'=>$member->name,'password'=>$data['password'],'role'=>'brand_member','email_verified_at'=>now()]);$user->update(['password'=>$data['password'],'email_verified_at'=>now()]);$member->update(['user_id'=>$user->id,'status'=>'active','joined_at'=>now(),'invitation_token_hash'=>null]);return $user;});Auth::login($user);$r->session()->regenerate();return to_route('member.tasks.index');}
}
