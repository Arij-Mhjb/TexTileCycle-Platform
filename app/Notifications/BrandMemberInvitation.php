<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class BrandMemberInvitation extends Notification {
 use Queueable;
 public function __construct(public string $brandName,public string $memberName,public string $url){}
 public function via(object $notifiable):array{return ['mail'];}
 public function toMail(object $notifiable):MailMessage{return (new MailMessage)->subject("Invitation à rejoindre {$this->brandName} sur TexTileCycle")->greeting('Bonjour '.$this->memberName.',')->line("{$this->brandName} vous invite à suivre ses démarches de labellisation écologique.")->action('Activer mon accès',$this->url)->line('Vous pourrez uniquement consulter et réaliser les tâches qui vous sont affectées.');}
}
