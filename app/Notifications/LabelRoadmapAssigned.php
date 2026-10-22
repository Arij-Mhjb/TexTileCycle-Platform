<?php
namespace App\Notifications;
use App\Models\LabelRoadmap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
class LabelRoadmapAssigned extends Notification implements ShouldQueue {
 use Queueable;
 public int $tries = 3;
 public int $timeout = 120;
 public function __construct(public LabelRoadmap $roadmap){$this->afterCommit();}
 public function via(object $notifiable):array{return ['mail'];}
 public function toMail(object $notifiable):MailMessage
 {
  $memberId=$this->roadmap->brand_member_id;
  $firstStep=$memberId ? $this->roadmap->steps()->where('brand_member_id',$memberId)->orderBy('position')->first() : null;
  $url=$firstStep
   ? URL::temporarySignedRoute('member.tasks.email-access',now()->addDays(7),['step'=>$firstStep->id,'member'=>$memberId])
   : route('member.tasks.index');

  return (new MailMessage)->subject('Responsabilité label : '.$this->roadmap->label_snapshot['name'])->greeting('Bonjour '.$notifiable->name.',')->line($this->roadmap->brand->name.' vous confie la démarche complète du label '.$this->roadmap->label_snapshot['name'].'.')->line('Projet : '.$this->roadmap->name.' · priorité '.str($this->roadmap->assignment_priority)->headline())->line($this->roadmap->assignment_comment ?: 'Vous êtes responsable du suivi des étapes et des preuves de cette démarche.')->action('Commencer la première tâche',$url)->line('Ce lien personnel est valable pendant 7 jours. Les autres tâches du label restent accessibles depuis « Mes tâches ».');
 }
}
