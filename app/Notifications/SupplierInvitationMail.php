<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** E-mail envoyé au fournisseur quand la marque lui ouvre un espace : lien d'activation à usage unique (72 h). */
class SupplierInvitationMail extends Notification
{
    public function __construct(
        public string $brandName,
        public string $supplierName,
        public string $url,
        public string $expiresAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->brandName.' vous invite sur TexTileCycle')
            ->greeting('Bonjour '.$this->supplierName.',')
            ->line($this->brandName.' vous ouvre un espace fournisseur sur TexTileCycle.')
            ->line('Vous y recevrez ses bons de commande, déclarerez vos livraisons avec leurs certificats de provenance et traiterez ses demandes de recyclage.')
            ->action('Activer mon espace fournisseur', $this->url)
            ->line('Vous choisirez vous-même votre mot de passe. Ce lien ne fonctionne qu’une fois et expire le '.$this->expiresAt.'.')
            ->salutation('L’équipe TexTileCycle');
    }
}
