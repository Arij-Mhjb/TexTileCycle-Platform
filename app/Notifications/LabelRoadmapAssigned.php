<?php
namespace App\Notifications;
use App\Models\LabelRoadmap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class LabelRoadmapAssigned extends Notification implements ShouldQueue {
 use Queueable;
 public int $tries = 3;
 public int $timeout = 120;
 public function __construct(public LabelRoadmap $roadmap){$this->afterCommit();}
 public function via(object $notifiable):array{return ['mail'];}
 public function toMail(object $notifiable):MailMessage{return (new MailMessage)->subject('Responsabilité label : '.$this->roadmap->label_snapshot['name'])->greeting('Bonjour '.$notifiable->name.',')->line($this->roadmap->brand->name.' vous confie la démarche complète du label '.$this->roadmap->label_snapshot['name'].'.')->line('Projet : '.$this->roadmap->name.' · priorité '.str($this->roadmap->assignment_priority)->headline())->line($this->roadmap->assignment_comment ?: 'Vous êtes responsable du suivi des étapes et des preuves de cette démarche.')->action('Voir les tâches du label',route('member.tasks.index'));}
}
