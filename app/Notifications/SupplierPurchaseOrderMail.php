<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail envoyé au fournisseur avec le bon de commande et son lien sécurisé.
 */
class SupplierPurchaseOrderMail extends Notification
{
    public function __construct(public PurchaseOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $mail = (new MailMessage)
            ->subject('Bon de commande '.$order->reference.' · '.$order->brand->name)
            ->greeting('Bonjour '.($order->supplier->contact_name ?: $order->supplier->name).',')
            ->line($order->brand->name.' vous adresse le bon de commande **'.$order->reference.'**.')
            ->line('Commandé le '.$order->ordered_at->format('d/m/Y').($order->expected_at ? ' · livraison souhaitée le '.$order->expected_at->format('d/m/Y') : '')
                .($order->total_amount ? ' · montant : '.number_format((float) $order->total_amount, 2, ',', ' ').' '.$order->currency : '').'.');
        if ($order->notes) {
            $mail->line('Note : '.$order->notes);
        }

        return $mail
            ->line('Cliquez sur le bouton pour consulter le détail de la commande et l’accepter ou la refuser. Une fois acceptée, vous pourrez y déclarer l’expédition avec la photo du certificat de provenance (GRS, GOTS, RWS…). Aucun compte n’est nécessaire.')
            ->action('Voir la commande et répondre', $order->supplierPortalUrl())
            ->line('Ce lien est personnel et valable 60 jours.')
            ->salutation('L’équipe TexTileCycle');
    }
}
