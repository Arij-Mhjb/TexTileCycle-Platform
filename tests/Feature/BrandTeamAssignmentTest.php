<?php
namespace Tests\Feature;
use App\Models\Brand;
use App\Models\BrandMember;
use App\Models\Label;
use App\Models\LabelRoadmap;
use App\Models\Product;
use App\Models\RoadmapSubmission;
use App\Models\RoadmapStep;
use App\Models\User;
use App\Notifications\BrandMemberInvitation;
use App\Notifications\RoadmapStepAssigned;
use App\Notifications\LabelRoadmapAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;
class BrandTeamAssignmentTest extends TestCase {
 use RefreshDatabase;
 private function owner():User{$u=User::factory()->create(['role'=>'brand']);Brand::create(['user_id'=>$u->id,'name'=>'Atelier','slug'=>'atelier','is_active'=>true]);return $u;}
 public function test_brand_invites_member_and_member_activates_restricted_account():void{Notification::fake();$owner=$this->owner();$this->actingAs($owner)->post(route('brand.team.store'),['name'=>'Leila','email'=>'leila@atelier.test','department'=>'RSE','job_title'=>'Responsable'])->assertSessionHasNoErrors();$member=BrandMember::firstOrFail();Notification::assertSentOnDemand(BrandMemberInvitation::class);$token='known-token';$member->update(['invitation_token_hash'=>hash('sha256',$token)]);auth()->logout();$this->post(route('brand-member.invitation.store',$token),['password'=>'password123','password_confirmation'=>'password123'])->assertRedirect(route('member.tasks.index'));$this->assertSame('brand_member',$member->fresh()->user->role);$this->get(route('brand.dashboard'))->assertForbidden();}
 public function test_invitation_mail_supports_anonymous_recipient():void{$mail=(new BrandMemberInvitation('Atelier','Leila','https://example.test/invite'))->toMail((new AnonymousNotifiable)->route('mail','leila@example.test'));$this->assertSame('Bonjour Leila,',$mail->greeting);}
 public function test_existing_brand_account_can_accept_collaborator_invitation():void{$existing=$this->owner();$otherOwner=User::factory()->create(['role'=>'brand']);$other=Brand::create(['user_id'=>$otherOwner->id,'name'=>'Autre marque','slug'=>'autre-marque','is_active'=>true]);$token='existing-brand-token';$member=$other->members()->create(['name'=>$existing->name,'email'=>$existing->email,'department'=>'Qualité','status'=>'invited','invitation_token_hash'=>hash('sha256',$token),'invited_at'=>now()]);$this->post(route('brand-member.invitation.store',$token),['password'=>'new-password','password_confirmation'=>'new-password'])->assertRedirect(route('member.tasks.index'));$this->assertSame('brand',$existing->fresh()->role);$this->assertSame($existing->id,$member->fresh()->user_id);$this->get(route('member.tasks.index'))->assertOk();}
 public function test_brand_without_membership_is_redirected_instead_of_getting_an_error():void{$owner=$this->owner();$this->actingAs($owner)->get(route('member.tasks.index'))->assertRedirect(route('brand.dashboard'))->assertSessionHas('error');}
 public function test_assignment_sends_email_and_member_sees_only_assigned_step():void{Notification::fake();$owner=$this->owner();$memberUser=User::factory()->create(['role'=>'brand_member']);$member=$owner->brand->members()->create(['user_id'=>$memberUser->id,'name'=>'Leila','email'=>$memberUser->email,'department'=>'RSE','status'=>'active','joined_at'=>now()]);$label=Label::create(['name'=>'GRS','slug'=>'grs-team','pillar'=>'circularity','description'=>'Test','is_active'=>true]);$roadmap=LabelRoadmap::create(['brand_id'=>$owner->brand->id,'label_id'=>$label->id,'name'=>'Projet','scope_description'=>'Scope','baseline'=>'Base','objective'=>'Goal','starts_on'=>today(),'target_date'=>today()->addMonth(),'label_snapshot'=>$label->only('id','name')]);$step=$roadmap->steps()->create(['title'=>'Collecter les preuves','instructions'=>'Faire','required_evidence'=>'PDF','position'=>1,'due_date'=>today()->addWeek(),'owner_name'=>'Owner','goal'=>'Préparer les preuves','status'=>'todo']);$this->actingAs($owner)->patch(route('brand.roadmaps.steps.update',$step),['goal'=>'Préparer les preuves','owner_name'=>'Leila','due_date'=>today()->addWeek()->format('Y-m-d'),'status'=>'todo','brand_member_id'=>$member->id,'priority'=>'high','assignment_comment'=>'Vérifier les certificats'])->assertSessionHasNoErrors();Notification::assertSentTo($memberUser,RoadmapStepAssigned::class);$this->actingAs($memberUser)->get(route('member.tasks.index'))->assertOk()->assertSee('Collecter les preuves')->assertSee('Vérifier les certificats');}
 public function test_member_opens_emailed_task_submits_proof_and_admin_approves_it():void
 {
     Notification::fake();
     config(['roadmaps.proof_storage' => 'local']);
     Storage::fake('local');
     $owner=$this->owner();
     $memberUser=User::factory()->create(['role'=>'brand_member']);
     $member=$owner->brand->members()->create(['user_id'=>$memberUser->id,'name'=>'Leila','email'=>$memberUser->email,'department'=>'RSE','status'=>'active','joined_at'=>now()]);
     $label=Label::create(['name'=>'GRS','slug'=>'grs-complete-flow','pillar'=>'circularity','description'=>'Test','is_active'=>true]);
     $product=Product::create(['brand_id'=>$owner->brand->id,'brand'=>'Atelier','name'=>'Veste recyclée','slug'=>'veste-flow','sku'=>'FLOW-1','category'=>'Veste']);
     $roadmap=LabelRoadmap::create(['brand_id'=>$owner->brand->id,'label_id'=>$label->id,'name'=>'Projet GRS','scope_description'=>'Veste','baseline'=>'20 % recyclé','objective'=>'100 % recyclé','starts_on'=>today(),'target_date'=>today()->addMonth(),'label_snapshot'=>$label->only('id','name')]);
     $roadmap->products()->attach($product->id,['snapshot'=>json_encode(['id'=>$product->id,'name'=>$product->name,'sku'=>$product->sku])]);
     $step=$roadmap->steps()->create(['title'=>'Collecter les certificats','instructions'=>'Réunir les certificats fournisseurs','required_evidence'=>'Certificat PDF','position'=>1,'due_date'=>today()->addWeek(),'owner_name'=>'Owner','goal'=>'Préparer les preuves','status'=>'todo']);

     $this->actingAs($owner)->patch(route('brand.roadmaps.steps.update',$step),['goal'=>'Préparer les preuves','owner_name'=>'Leila','due_date'=>today()->addWeek()->format('Y-m-d'),'status'=>'todo','brand_member_id'=>$member->id,'priority'=>'high','assignment_comment'=>'Vérifier les certificats'])->assertSessionHasNoErrors();
     $taskAccessUrl=null;
     Notification::assertSentTo($memberUser,RoadmapStepAssigned::class,function(RoadmapStepAssigned $notification)use($memberUser,$step,&$taskAccessUrl){$taskAccessUrl=$notification->toMail($memberUser)->actionUrl;return str_contains($taskAccessUrl,"/acces-tache/{$step->id}/");});

     $this->actingAs($owner)->get($taskAccessUrl)->assertOk()->assertSee($member->email)->assertSee('Accéder à ma tâche');
     $invalidSignedUrl=preg_replace('/signature=[^&]+/','signature=invalid',$taskAccessUrl);
     $this->post($invalidSignedUrl,['password'=>'password'])->assertForbidden();
     $this->post($taskAccessUrl,['password'=>'incorrect'])->assertSessionHasErrors('password');
     $this->post($taskAccessUrl,['password'=>'password'])->assertRedirect(route('member.tasks.show',$step));
     $this->assertAuthenticatedAs($memberUser);
     $this->get(route('member.tasks.show',$step))->assertOk()->assertSee('Collecter les certificats');
     $this->post(route('member.tasks.submit',$step),[
         'scope_version'=>$roadmap->scope_version,
         'actions'=>'Collecte et contrôle des certificats fournisseurs.',
         'results'=>'Le certificat du fournisseur principal est conforme.',
         'product_results'=>[$product->id=>'Certificat valide reçu'],
         'proofs'=>[UploadedFile::fake()->create('certificat.pdf',100,'application/pdf')],
     ])->assertSessionHasNoErrors()->assertRedirect();

     $submission=RoadmapSubmission::firstOrFail();
     $this->assertSame($memberUser->id,$submission->submitted_by);
     $this->assertSame('submitted',$step->fresh()->status);
     $this->get(route('member.tasks.show',$step))->assertOk()->assertSee('Soumission envoyée à l’administrateur')->assertDontSee('Envoyer à l’administrateur');

     $admin=User::factory()->create(['role'=>'admin']);
     $this->actingAs($admin)->get(route('admin.roadmaps.index'))->assertOk()->assertSee('Collecter les certificats');
     $this->patch(route('admin.roadmaps.review',$submission),['status'=>'approved','review_comment'=>'Certificat vérifié et conforme.'])->assertSessionHasNoErrors()->assertRedirect();
     $this->assertSame('approved',$submission->fresh()->status);
     $this->assertSame('approved',$step->fresh()->status);
     $this->actingAs($memberUser)->get(route('member.tasks.show',$step))->assertOk()->assertSee('Étape validée');
 }
 public function test_brand_can_assign_the_whole_label_to_one_member():void{Notification::fake();$owner=$this->owner();$memberUser=User::factory()->create(['role'=>'brand_member']);$member=$owner->brand->members()->create(['user_id'=>$memberUser->id,'name'=>'Leila','email'=>$memberUser->email,'department'=>'RSE','status'=>'active']);$label=Label::create(['name'=>'GOTS','slug'=>'gots-team','pillar'=>'circularity','description'=>'Test','is_active'=>true]);$roadmap=LabelRoadmap::create(['brand_id'=>$owner->brand->id,'label_id'=>$label->id,'name'=>'Collection GOTS','scope_description'=>'Scope','baseline'=>'Base','objective'=>'Goal','starts_on'=>today(),'target_date'=>today()->addMonth(),'label_snapshot'=>$label->only('id','name')]);foreach(['Fournisseurs','Tests','Audit'] as $i=>$title)$roadmap->steps()->create(['title'=>$title,'instructions'=>'Faire','required_evidence'=>'PDF','position'=>$i+1,'due_date'=>today()->addDays(5+$i),'owner_name'=>'Owner','goal'=>'Réaliser','status'=>'todo']);$this->actingAs($owner)->patch(route('brand.roadmaps.assign',$roadmap),['brand_member_id'=>$member->id,'assignment_priority'=>'urgent','assignment_comment'=>'Piloter toute la certification'])->assertSessionHasNoErrors();$this->assertSame($member->id,$roadmap->fresh()->brand_member_id);$this->assertSame(3,$roadmap->steps()->where('brand_member_id',$member->id)->where('priority','urgent')->count());$firstStep=$roadmap->steps()->orderBy('position')->firstOrFail();Notification::assertSentTo($memberUser,LabelRoadmapAssigned::class,function(LabelRoadmapAssigned $notification)use($memberUser,$firstStep,$member){$url=$notification->toMail($memberUser)->actionUrl;return str_contains($url,"/acces-tache/{$firstStep->id}/{$member->id}")&&str_contains($url,'signature=');});}
}

