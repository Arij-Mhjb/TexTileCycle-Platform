<?php
namespace App\Notifications;
use App\Models\RoadmapStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
class RoadmapStepAssigned extends Notification implements ShouldQueue {
 use Queueable;
 public int $tries = 3;
 public int $timeout = 120;
 public function __construct(public RoadmapStep $step){$this->afterCommit();}
 public function via(object $notifiable):array{return ['mail'];}
 public function toMail(object $notifiable):MailMessage
 {
  $member=$this->step->assignee;
  $url=URL::temporarySignedRoute('member.tasks.email-access',now()->addDays(7),['step'=>$this->step->id,'member'=>$member->id]);

  return (new MailMessage)->subject('Nouvelle tâche TexTileCycle : '.$this->step->title)->greeting('Bonjour '.$notifiable->name.',')->line('Une étape vous a été affectée pour '.$this->step->roadmap->label_snapshot['name'].'.')->line('Priorité : '.str($this->step->priority)->headline().' · échéance '.$this->step->due_date->format('d/m/Y'))->line($this->step->assignment_comment ?: $this->step->goal)->action('Voir ma tâche',$url)->line('Ce lien personnel est valable pendant 7 jours.');
 }
}
